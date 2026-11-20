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

#[Description('Write a note in a vault\'s Knowledge. The text is Markdown: # for a heading, - for a bullet, - [ ] for a task, > for a quote. Without a folder it goes to the first folder, and a vault without any gets "General". A note links to no contact, possession or other note. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'folder_id' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        if (isset($validated['folder_id'])) {
            $this->knowledgeFolder($vault, $validated['folder_id']);
        }

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'title' => $schema->string()->description('The title of the note. "Untitled note" when left out.'),
            'body' => $schema->string()->description('The text of the note, in Markdown.'),
            'folder_id' => $schema->string()->description('The id of the folder to write the note in, as list-knowledge gives it.'),
        ];
    }
}
