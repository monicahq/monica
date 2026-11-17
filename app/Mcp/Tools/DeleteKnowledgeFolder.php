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

#[Description('Delete a folder of a vault\'s Knowledge. Its notes and lists move to the folder given as move_to. Without move_to, they are deleted with the folder, and no tool brings them back. The last folder of a vault cannot be deleted. The change is written to the account log.')]
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
        $folder = $this->knowledgeFolder($vault, $validated['folder']);

        if (isset($validated['move_to'])) {
            $this->knowledgeFolder($vault, $validated['move_to']);
        }

        $answer = $this->answerThroughApi($request, $vault->uuid, $folder->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The folder is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'folder' => $schema->string()->description('The id of the folder to delete, as list-knowledge gives it.')->required(),
            'move_to' => $schema->string()->description('The id of another folder of the vault, as list-knowledge gives it, to move the notes and lists of the deleted folder to. Without it, the notes and lists are deleted too.'),
        ];
    }
}
