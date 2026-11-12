<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyKnowledgeListColumn;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove a column from a list of a vault\'s Knowledge, with what every item holds in it. No tool brings it back. The last column of a list cannot be removed. The change is written to the account journal.')]
#[IsDestructive]
class DeleteKnowledgeListColumn extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.columns.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'column' => ['required', 'string'],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        try {
            new DestroyKnowledgeListColumn(
                user: $this->user($request),
                column: $this->knowledgeListColumn($list, $validated['column']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The column is removed.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'column' => $schema->string()->description('The identifier of the column, as get-knowledge-list gives it.')->required(),
        ];
    }
}
