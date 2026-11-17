<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search the people, entities and life events of a vault, or of every vault, for every word given, typing mistakes tolerated. The results come back in result groups, one per kind of record.')]
#[IsReadOnly]
class SearchRecords extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.search';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'query' => $schema->string()->description('The words to find, at least two characters.')->required(),
            'scope' => $schema->string()->enum(['vault', 'account'])->description('vault to search that vault, account to search every vault of the account. vault when left out.'),
            'group' => $schema->string()->description('One result group only, by the key an earlier answer of search-records gave it.'),
            'page' => $schema->integer()->description('The page of each result group to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many results a result group holds per page: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
