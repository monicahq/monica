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

#[Description('Change a note of a vault\'s Knowledge: its title, its text, or the folder it sits in. The text replaces the whole text, so read the note with get-knowledge-note first and send it back changed. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'note' => ['required', 'string'],
            'folder_id' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $note = $this->knowledgeNote($vault, $validated['note']);

        if (isset($validated['folder_id'])) {
            $this->knowledgeFolder($vault, $validated['folder_id']);
        }

        return $this->answerThroughApi($request, $vault->uuid, $note->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'note' => $schema->string()->description('The id of the note, as list-knowledge gives it.')->required(),
            'title' => $schema->string()->description('The new title. A blank title keeps the title the note has.'),
            'body' => $schema->string()->description('The whole new text, in Markdown. An empty text clears the text of the note.'),
            'folder_id' => $schema->string()->description('The id of the folder to move the note to, as list-knowledge gives it.'),
        ];
    }
}
