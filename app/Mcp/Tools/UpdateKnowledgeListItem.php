<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateKnowledgeListItem as UpdateKnowledgeListItemAction;
use App\Helpers\KnowledgeListCells;
use App\Http\Resources\KnowledgeListResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change the texts of an item of a list of a vault\'s Knowledge. Only the columns given change, and an empty text empties its cell. An item left with every text blank is refused. Answers with the whole list. The change is written to the account journal.')]
class UpdateKnowledgeListItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.items.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'item' => ['required', 'string'],
            'cells' => ['required', 'array'],
            'cells.*' => ['nullable', 'string', 'max:'.KnowledgeListCells::MAX_LENGTH],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        new UpdateKnowledgeListItemAction(
            user: $this->user($request),
            item: $this->knowledgeListItem($list, $validated['item']),
            cells: $validated['cells'],
        )->execute();

        return Response::json(new KnowledgeListResource($list->refresh()->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'item' => $schema->string()->description('The identifier of the item, as get-knowledge-list gives it.')->required(),
            'cells' => $schema->object()->description('The texts to change, keyed by the identifier of their column.')->required(),
        ];
    }
}
