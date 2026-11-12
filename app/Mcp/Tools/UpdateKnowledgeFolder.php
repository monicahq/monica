<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateKnowledgeFolder as UpdateKnowledgeFolderAction;
use App\Actions\UpdateKnowledgeFolderPosition;
use App\Http\Resources\KnowledgeFolderResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a folder of a vault\'s Knowledge, move it in the order of the folders, or both. What is not given stays as it is. The change is written to the account journal.')]
class UpdateKnowledgeFolder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.folders.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'folder' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $folder = $this->knowledgeFolder($this->vault($request, $validated['vault']), $validated['folder']);

        if (isset($validated['name'])) {
            new UpdateKnowledgeFolderAction(user: $this->user($request), folder: $folder, name: $validated['name'])->execute();
        }

        if (isset($validated['position'])) {
            new UpdateKnowledgeFolderPosition(user: $this->user($request), folder: $folder, position: (int) $validated['position'])->execute();
        }

        return Response::json(new KnowledgeFolderResource($folder->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'folder' => $schema->string()->description('The identifier of the folder, as list-knowledge gives it.')->required(),
            'name' => $schema->string()->description('The new name of the folder.'),
            'position' => $schema->integer()->description('Where the folder goes in the order, 1 for first. A number past the end puts it last.'),
        ];
    }
}
