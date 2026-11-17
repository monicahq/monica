<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Start a list in a vault\'s Knowledge, with the columns "Name" and "Note". Rename or add columns with update-knowledge-list-column and create-knowledge-list-column, then add items with create-knowledge-list-item. Without a folder it goes to the first folder. The change is written to the account log.')]
class CreateKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.create';

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
            'title' => $schema->string()->description('The title of the list. "Untitled list" when left out.'),
            'description' => $schema->string()->description('What the list is for, in Markdown.'),
            'folder_id' => $schema->string()->description('The id of the folder to put the list in, as list-knowledge gives it.'),
        ];
    }
}
