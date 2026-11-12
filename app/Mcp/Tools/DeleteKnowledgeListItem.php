<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyKnowledgeListItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete an item of a list of a vault\'s Knowledge for good, with everything written in it. No tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
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

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        new DestroyKnowledgeListItem(
            user: $this->user($request),
            item: $this->knowledgeListItem($list, $validated['item']),
        )->execute();

        return Response::text(__('The item is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'item' => $schema->string()->description('The identifier of the item, as get-knowledge-list gives it.')->required(),
        ];
    }
}
