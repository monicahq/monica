<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a column of a list of a vault\'s Knowledge, move it among the columns, or both. Answers with the whole list. The change is written to the account log.')]
class UpdateKnowledgeListColumn extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.columns.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'column' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $list = $this->knowledgeList($vault, $validated['list']);
        $column = $this->knowledgeListColumn($list, $validated['column']);

        return $this->answerThroughApi($request, $vault->uuid, $list->uuid, $column->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'list' => $schema->string()->description('The id of the list, as list-knowledge gives it.')->required(),
            'column' => $schema->string()->description('The id of the column, as get-knowledge-list gives it.')->required(),
            'name' => $schema->string()->description('The new name of the column.'),
            'position' => $schema->integer()->description('Where the column goes, 1 for first. A number past the end puts the column last.'),
        ];
    }
}
