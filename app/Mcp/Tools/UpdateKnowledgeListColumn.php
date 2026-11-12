<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateKnowledgeListColumn as UpdateKnowledgeListColumnAction;
use App\Actions\UpdateKnowledgeListColumnPosition;
use App\Http\Resources\KnowledgeListResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a column of a list of a vault\'s Knowledge, move it among the columns, or both. Answers with the whole list. The change is written to the account journal.')]
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
            'name' => ['sometimes', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);
        $column = $this->knowledgeListColumn($list, $validated['column']);

        if (isset($validated['name'])) {
            new UpdateKnowledgeListColumnAction(user: $this->user($request), column: $column, name: $validated['name'])->execute();
        }

        if (isset($validated['position'])) {
            new UpdateKnowledgeListColumnPosition(user: $this->user($request), column: $column, position: (int) $validated['position'])->execute();
        }

        return Response::json(new KnowledgeListResource($list->refresh()->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'column' => $schema->string()->description('The identifier of the column, as get-knowledge-list gives it.')->required(),
            'name' => $schema->string()->description('The new name of the column.'),
            'position' => $schema->integer()->description('Where the column goes, 1 for first. A number past the end puts it last.'),
        ];
    }
}
