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

#[Description('Rename a folder of a vault\'s Knowledge, move it in the order of the folders, or both. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateKnowledgeFolder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.folders.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'folder' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $folder = $this->knowledgeFolder($vault, $validated['folder']);

        return $this->answerThroughApi($request, $vault->uuid, $folder->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'folder' => $schema->string()->description('The id of the folder, as list-knowledge gives it.')->required(),
            'name' => $schema->string()->description('The new name of the folder.'),
            'position' => $schema->integer()->description('Where the folder goes in the order, 1 for first. A number past the end puts the folder last.'),
        ];
    }
}
