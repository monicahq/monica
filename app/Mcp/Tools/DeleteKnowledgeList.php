<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyKnowledgeList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a list of a vault\'s Knowledge for good, with its columns and every item in it. No tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
class DeleteKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
        ]);

        new DestroyKnowledgeList(
            user: $this->user($request),
            list: $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']),
        )->execute();

        return Response::text(__('The list is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
        ];
    }
}
