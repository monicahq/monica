<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateKnowledgeListItem as CreateKnowledgeListItemAction;
use App\Helpers\KnowledgeListCells;
use App\Http\Resources\KnowledgeListResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add an item to a list of a vault\'s Knowledge, after its other items. Its texts are keyed by the identifier of their column, as get-knowledge-list gives it, and take Markdown. An item with every text blank is refused. Answers with the whole list. The change is written to the account journal.')]
class CreateKnowledgeListItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.items.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'cells' => ['required', 'array'],
            'cells.*' => ['nullable', 'string', 'max:'.KnowledgeListCells::MAX_LENGTH],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        new CreateKnowledgeListItemAction(user: $this->user($request), list: $list, cells: $validated['cells'])->execute();

        return Response::json(new KnowledgeListResource($list->refresh()->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'cells' => $schema->object()->description('The texts of the item, keyed by the identifier of their column.')->required(),
        ];
    }
}
