<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyKnowledgeFolder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a folder of a vault\'s Knowledge. Its notes and lists move to the folder given as move_to; without it they are deleted with the folder, and no tool brings them back. The last folder of a vault cannot be deleted. The change is written to the account journal.')]
#[IsDestructive]
class DeleteKnowledgeFolder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.folders.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'folder' => ['required', 'string'],
            'move_to' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $moveTo = isset($validated['move_to']) ? $this->knowledgeFolder($vault, $validated['move_to']) : null;

        try {
            new DestroyKnowledgeFolder(
                user: $this->user($request),
                folder: $this->knowledgeFolder($vault, $validated['folder']),
                moveTo: $moveTo,
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::text(__('The folder is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'folder' => $schema->string()->description('The identifier of the folder to delete, as list-knowledge gives it.')->required(),
            'move_to' => $schema->string()->description('The identifier of another folder of the vault to move its notes and lists to. Leave it out to delete them too.'),
        ];
    }
}
