<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossession;
use App\Actions\DestroyPossession;
use App\Actions\UpdatePossession;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionStatusEnum;
use App\Helpers\LocationTree;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompactPossessionResource;
use App\Http\Resources\PossessionResource;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionType;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'type' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::enum(PossessionStatusEnum::class)],
            'featured' => ['nullable', 'boolean'],
            'location' => ['nullable', 'uuid', Rule::exists(Location::class, 'uuid')->where('account_id', $vault->account_id)],
            'sort' => ['nullable', 'string', Rule::in(['name', 'position'])],
            'compact' => ['nullable', 'boolean'],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $isCompact = $request->boolean('compact');

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
            $query->where('is_featured', $request->boolean('featured'));
        }

        if (isset($validated['location'])) {
            $tree = LocationTree::forAccount($vault->account_id);
            $location = Location::query()->where('account_id', $vault->account_id)->where('uuid', $validated['location'])->firstOrFail();

            $query->whereIn('current_location_id', [$location->id, ...$tree->descendantIds($location)]);
        }

        if (($validated['sort'] ?? 'name') === 'position') {
            $query->orderByRaw('position is null')->orderBy('position');
        }

        $possessions = $query->orderBy('name')->orderBy('id')->paginate($perPage);

        return response()->json([
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

    public function show(Request $request, string $vault, string $possession): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        return new PossessionResource($this->possession($vault, $possession))->response();
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $validated = $request->validate([
            ...$this->rules($request, $vault),
            'type_id' => ['bail', 'required', 'uuid', Rule::exists('possession_types', 'uuid')->where('account_id', $request->user()->account_id)],
        ]);

        $possession = new CreatePossession(
            user: $request->user(),
            vault: $vault,
            possessionType: PossessionType::query()->where('account_id', $request->user()->account_id)->where('uuid', $validated['type_id'])->firstOrFail(),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            location: $this->location($request, $validated['location_id'] ?? null),
            identifier: $validated['identifier'] ?? null,
            status: PossessionStatusEnum::from($validated['status'] ?? PossessionStatusEnum::Owned->value),
            quantity: (int) ($validated['quantity'] ?? 1),
            isFeatured: $request->boolean('is_featured'),
            declaredValue: isset($validated['declared_value']) ? (string) $validated['declared_value'] : null,
            declaredValueCurrency: CurrencyEnum::tryFrom($validated['declared_value_currency'] ?? ''),
            tagNames: $validated['tags'] ?? [],
        )->execute();

        return new PossessionResource($possession)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $possession): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $record = $this->possession($vault, $possession);
        $validated = $request->validate($this->rules($request, $vault, isCreating: false));
        $declaredValue = array_key_exists('declared_value', $validated) ? $validated['declared_value'] : $record->declared_value;
        $declaredValueCurrency = array_key_exists('declared_value_currency', $validated) ? $validated['declared_value_currency'] : $record->declared_value_currency;

        new UpdatePossession(
            user: $request->user(),
            possession: $record,
            name: $validated['name'] ?? $record->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $record->description,
            location: array_key_exists('location_id', $validated) ? $this->location($request, $validated['location_id']) : $record->location,
            identifier: array_key_exists('identifier', $validated) ? $validated['identifier'] : $record->identifier,
            status: isset($validated['status']) ? PossessionStatusEnum::from($validated['status']) : $record->status,
            quantity: (int) ($validated['quantity'] ?? $record->quantity),
            isFeatured: array_key_exists('is_featured', $validated) ? (bool) $validated['is_featured'] : $record->is_featured,
            declaredValue: $declaredValue === null ? null : (string) $declaredValue,
            declaredValueCurrency: CurrencyEnum::tryFrom($declaredValueCurrency ?? ''),
            tagNames: array_key_exists('tags', $validated) ? $validated['tags'] ?? [] : null,
        )->execute();

        return new PossessionResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession): Response
    {
        $vault = $this->vault($request, $vault);

        new DestroyPossession(
            user: $request->user(),
            possession: $this->possession($vault, $possession),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request, Vault $vault, bool $isCreating = true): array
    {
        $optionalOnUpdate = $isCreating ? 'nullable' : 'sometimes';

        return [
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_id' => ['bail', 'nullable', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'identifier' => ['nullable', 'string', 'max:255'],
            'status' => [$optionalOnUpdate, Rule::enum(PossessionStatusEnum::class)],
            'quantity' => [$optionalOnUpdate, 'integer', 'min:1', 'max:1000000'],
            'is_featured' => [$optionalOnUpdate, 'boolean'],
            'acquisition' => ['prohibited'],
            'acquired_from_id' => ['prohibited'],
            'acquired_on' => ['prohibited'],
            'declared_value' => ['nullable', 'numeric'],
            'declared_value_currency' => ['nullable', 'required_with:declared_value', Rule::enum(CurrencyEnum::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function possession(Vault $vault, string $uuid): Possession
    {
        return Possession::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function location(Request $request, ?string $uuid): ?Location
    {
        if ($uuid === null) {
            return null;
        }

        return Location::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
