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

#[Description('Add an item to a list of a vault\'s Knowledge, after its other items. Its texts are keyed by the id of their column, as get-knowledge-list gives it, and take Markdown. An item with every text blank is refused. Answers with the whole list. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateKnowledgeListItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.items.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $list = $this->knowledgeList($vault, $validated['list']);

        return $this->answerThroughApi($request, $vault->uuid, $list->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'list' => $schema->string()->description('The id of the list, as list-knowledge gives it.')->required(),
            'cells' => $schema->object()->description('The texts of the item, keyed by the id of their column.')->required(),
        ];
    }
}
