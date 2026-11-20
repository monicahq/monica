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

#[Description('Delete an item of a list of a vault\'s Knowledge for good, with everything written in it. No tool brings it back. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteKnowledgeListItem extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.items.destroy';

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

        $answer = $this->answerThroughApi($request, $vault->uuid, $list->uuid, $item->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The item is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'list' => $schema->string()->description('The id of the list, as list-knowledge gives it.')->required(),
            'item' => $schema->string()->description('The id of the item, as get-knowledge-list gives it.')->required(),
        ];
    }
}
