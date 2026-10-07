<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the contacts of one vault, a page at a time, optionally narrowed by a search or a grouping.')]
#[IsReadOnly]
class ListContacts extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'filter' => ['nullable', 'string', Rule::in(ContactGroupingEnum::values())],
            'query' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(ContactSortEnum::values())],
            'direction' => ['nullable', 'string', Rule::enum(SortDirectionEnum::class)],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $contacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::fromValueOrEveryone($validated['filter'] ?? null))
            ->matchingSearch($validated['query'] ?? '')
            ->sortedBy(
                ContactSortEnum::fromValueOrName($validated['sort'] ?? null),
                SortDirectionEnum::fromValueOrAscending($validated['direction'] ?? null),
            )
            ->with(['vault', 'structureVersion.structure.owner', 'avatar', 'nameRepresentations'])
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return Response::json([
            'data' => ContactResource::collection($contacts->items())->resolve(),
            'meta' => [
                'page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()
                ->description('The address of the vault, as list-vaults gives it.')
                ->required(),
            'query' => $schema->string()
                ->description('Words to search the contacts of that vault for.'),
            'filter' => $schema->string()
                ->description('Narrow the list to a grouping: '.implode(', ', ContactGroupingEnum::values()).'.'),
            'sort' => $schema->string()
                ->description('Order the list by: '.implode(', ', ContactSortEnum::values()).'.'),
            'direction' => $schema->string()
                ->description('Either asc or desc.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many contacts a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
