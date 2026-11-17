<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one note of a vault\'s Knowledge: its title, its folder, its text in Markdown as written, and its headings.')]
#[IsReadOnly]
class GetKnowledgeNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.notes.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'note' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $note = $this->knowledgeNote($vault, $validated['note']);

        return $this->answerThroughApi($request, $vault->uuid, $note->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'note' => $schema->string()->description('The id of the note, as list-knowledge gives it.')->required(),
        ];
    }
}
