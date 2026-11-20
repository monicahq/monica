<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Change the texts of an item of a list of a vault\'s Knowledge. Only the columns given change, and an empty text empties its cell. An item left with every text blank is refused. Answers with the whole list. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
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
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $list = $this->knowledgeList($vault, $validated['list']);
        $item = $this->knowledgeListItem($list, $validated['item']);

        return $this->answerThroughApi($request, $vault->uuid, $list->uuid, $item->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'list' => $schema->string()->description('The id of the list, as list-knowledge gives it.')->required(),
            'item' => $schema->string()->description('The id of the item, as get-knowledge-list gives it.')->required(),
            'cells' => $schema->object()->description('The texts to change, keyed by the id of their column.')->required(),
        ];
    }
}
