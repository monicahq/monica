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

#[Description('List the contacts of one vault, optionally narrowed by a search or a grouping.')]
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
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $contacts = Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::fromValueOrEveryone($validated['filter'] ?? null))
            ->matchingSearch($validated['query'] ?? '')
            ->sortedBy(
                ContactSortEnum::fromValueOrName($validated['sort'] ?? null),
                SortDirectionEnum::fromValueOrAscending($validated['direction'] ?? null),
            )
            ->with(['vault', 'structureVersion.structure.owner', 'avatar', 'nameRepresentations'])
            ->get();

        return Response::json(ContactResource::collection($contacts)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
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
        ];
    }
}
