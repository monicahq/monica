<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateKnowledgeNote as UpdateKnowledgeNoteAction;
use App\Http\Resources\KnowledgeNoteResource;
use App\Models\KnowledgeNote;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a note of a vault\'s Knowledge: its title, its text, or the folder it sits in. What is not given stays as it is. The text replaces the whole text, so read the note with get-knowledge-note first and send it back changed. The change is written to the account journal.')]
class UpdateKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'note' => ['required', 'string'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string', 'max:'.KnowledgeNote::MAX_BODY_LENGTH],
            'folder' => ['sometimes', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $note = $this->knowledgeNote($vault, $validated['note']);

        new UpdateKnowledgeNoteAction(
            user: $this->user($request),
            note: $note,
            title: array_key_exists('title', $validated) ? (string) $validated['title'] : null,
            body: array_key_exists('body', $validated) ? (string) $validated['body'] : null,
            folder: isset($validated['folder']) ? $this->knowledgeFolder($vault, $validated['folder']) : null,
        )->execute();

        return Response::json(new KnowledgeNoteResource($note->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'note' => $schema->string()->description('The identifier of the note, as list-knowledge gives it.')->required(),
            'title' => $schema->string()->description('The new title. A blank title keeps the one it has.'),
            'body' => $schema->string()->description('The whole new text, in Markdown. An empty text clears it.'),
            'folder' => $schema->string()->description('The identifier of the folder to move it to.'),
        ];
    }
}
