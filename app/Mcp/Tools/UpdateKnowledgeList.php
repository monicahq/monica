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

#[Description('Change a list of a vault\'s Knowledge: its title, its description, or the folder it sits in. Its columns and items change through their own tools. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'folder_id' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $list = $this->knowledgeList($vault, $validated['list']);

        if (isset($validated['folder_id'])) {
            $this->knowledgeFolder($vault, $validated['folder_id']);
        }

        return $this->answerThroughApi($request, $vault->uuid, $list->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'list' => $schema->string()->description('The id of the list, as list-knowledge gives it.')->required(),
            'title' => $schema->string()->description('The new title. A blank title keeps the title the list has.'),
            'description' => $schema->string()->description('The new description, in Markdown. An empty description clears the description of the list.'),
            'folder_id' => $schema->string()->description('The id of the folder to move the list to, as list-knowledge gives it.'),
        ];
    }
}
