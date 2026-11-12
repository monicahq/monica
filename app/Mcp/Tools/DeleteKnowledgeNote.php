<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyKnowledgeNote;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a note of a vault\'s Knowledge for good. No tool brings it back. The change is written to the account journal.')]
#[IsDestructive]
class DeleteKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'note' => ['required', 'string'],
        ]);

        new DestroyKnowledgeNote(
            user: $this->user($request),
            note: $this->knowledgeNote($this->vault($request, $validated['vault']), $validated['note']),
        )->execute();

        return Response::text(__('The note is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'note' => $schema->string()->description('The identifier of the note, as list-knowledge gives it.')->required(),
        ];
    }
}
