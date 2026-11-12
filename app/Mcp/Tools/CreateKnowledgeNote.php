<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateKnowledgeNote as CreateKnowledgeNoteAction;
use App\Http\Resources\KnowledgeNoteResource;
use App\Models\KnowledgeNote;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write a note in a vault\'s Knowledge. The text is Markdown: # for a heading, - for a bullet, - [ ] for a task, > for a quote. Without a folder it goes to the first folder, and a vault without any gets "General". A note links to no contact, possession or other note. The change is written to the account journal.')]
class CreateKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:'.KnowledgeNote::MAX_BODY_LENGTH],
            'folder' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $note = new CreateKnowledgeNoteAction(
            user: $this->user($request),
            vault: $vault,
            folder: isset($validated['folder']) ? $this->knowledgeFolder($vault, $validated['folder']) : null,
            title: $validated['title'] ?? null,
            body: $validated['body'] ?? null,
        )->execute();

        return Response::json(new KnowledgeNoteResource($note)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'title' => $schema->string()->description('The title of the note. "Untitled note" when left out.'),
            'body' => $schema->string()->description('The text of the note, in Markdown.'),
            'folder' => $schema->string()->description('The identifier of the folder to write it in, as list-knowledge gives it.'),
        ];
    }
}
