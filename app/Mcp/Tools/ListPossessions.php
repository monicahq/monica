<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\PossessionStatusEnum;
use App\Helpers\LocationTree;
use App\Http\Resources\CompactPossessionResource;
use App\Http\Resources\PossessionResource;
use App\Models\Location;
use App\Models\Possession;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the things owned recorded in one vault, a page at a time, by name, optionally narrowed by type, status, location or whether they are featured. Each one carries its type, its location, the values of its fields and its current value, or only its name, status, type, location and current value when compact.')]
#[IsReadOnly]
class ListPossessions extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(PossessionStatusEnum::class)],
            'featured' => ['nullable', 'boolean'],
            'location' => ['nullable', 'uuid', Rule::exists(Location::class, 'uuid')->where('account_id', $this->user($request)->account_id)],
            'sort' => ['nullable', 'string', Rule::in(['name', 'position'])],
            'compact' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $isCompact = (bool) ($validated['compact'] ?? false);

        $query = Possession::query()
            ->where('vault_id', $vault->id)
            ->with($isCompact
                ? ['vault', 'possessionType', 'location', 'valuations']
                : ['vault', 'possessionType.groups.fields', 'possessionType.standaloneFields', 'location', 'values', 'transactions', 'tags', 'valuations']);

        if (isset($validated['type'])) {
            $query->whereRelation('possessionType', 'uuid', $validated['type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['featured'])) {
            $query->where('is_featured', (bool) $validated['featured']);
        }

        if (isset($validated['location'])) {
            $tree = LocationTree::forAccount($vault->account_id);
            $location = Location::query()->where('account_id', $vault->account_id)->where('uuid', $validated['location'])->firstOrFail();

            $query->whereIn('current_location_id', [$location->id, ...$tree->descendantIds($location)]);
        }

        if (($validated['sort'] ?? 'name') === 'position') {
            $query->orderByRaw('position is null')->orderBy('position');
        }

        $possessions = $query->orderBy('name')->orderBy('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => ($isCompact
                ? CompactPossessionResource::collection($possessions->items())
                : PossessionResource::collection($possessions->items()))->resolve(),
            'meta' => [
                'page' => $possessions->currentPage(),
                'per_page' => $possessions->perPage(),
                'total' => $possessions->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault, as list-vaults gives it.')->required(),
            'type' => $schema->string()->description('Only possessions of this type, by the identifier list-possession-types gives.'),
            'status' => $schema->string()->enum(array_column(PossessionStatusEnum::cases(), 'value'))->description('Only possessions with this status.'),
            'featured' => $schema->boolean()->description('Only featured possessions when true, only the others when false.'),
            'location' => $schema->string()->description('Only possessions at this location or inside it, by the identifier list-locations gives.'),
            'sort' => $schema->string()->enum(['name', 'position'])->description('By name, the default, or featured ones first in their order.'),
            'compact' => $schema->boolean()->description('When true, each possession carries only its name, status, type, location and current value. Use it to go through everything quickly, then get-possession for the full record.'),
            'page' => $schema->integer()->description('The page to read, from 1.'),
            'per_page' => $schema->integer()->description('How many possessions on a page.'),
        ];
    }
}
