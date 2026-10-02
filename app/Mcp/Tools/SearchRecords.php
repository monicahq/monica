<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\SearchRecords as SearchRecordsAction;
use App\Http\Resources\SearchResultResource;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search the people, entities and life events of a vault, or of every vault, for every word given, typing mistakes tolerated.')]
#[IsReadOnly]
class SearchRecords extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.search';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'scope' => ['nullable', 'string', 'in:vault,account'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $result = new SearchRecordsAction(
            user: $this->user($request),
            vault: $this->vault($request, $validated['vault']),
            query: $validated['query'],
            acrossAccount: ($validated['scope'] ?? 'vault') === 'account',
            group: $validated['group'] ?? null,
            withSensitive: false,
        )->execute();

        if ($result['status'] !== SearchRecordsAction::SEARCHED) {
            return Response::error(__('Search is unavailable right now'));
        }

        return Response::json(array_map(fn (array $group): array => [
            'group' => $group['key'],
            'label' => $group['label'],
            'total' => $group['results']->total(),
            'results' => collect($group['results']->items())->map(function (Contact|Entity|LifeEvent $record) use ($result): array {
                $resource = SearchResultResource::make($record);
                $resource->words = $result['words'];

                return $resource->toArray(new HttpRequest);
            })->values()->all(),
        ], $result['groups']));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()
                ->description('The address of the vault, as list-vaults gives it.')
                ->required(),
            'query' => $schema->string()
                ->description('The words to find, at least two characters.')
                ->required(),
            'scope' => $schema->string()
                ->description('vault to search that vault, account to search every vault of the account.'),
            'group' => $schema->string()
                ->description('One group only, by the key an earlier answer gave it.'),
        ];
    }
}
