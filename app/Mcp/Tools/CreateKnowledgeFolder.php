<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateKnowledgeFolder as CreateKnowledgeFolderAction;
use App\Http\Resources\KnowledgeFolderResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a folder to the Knowledge of a vault, after the folders already there. Folders are not nested. The change is written to the account journal.')]
class CreateKnowledgeFolder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.folders.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder = new CreateKnowledgeFolderAction(
            user: $this->user($request),
            vault: $this->vault($request, $validated['vault']),
            name: $validated['name'],
        )->execute();

        return Response::json(new KnowledgeFolderResource($folder)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'name' => $schema->string()->description('The name of the folder, such as Home or Travel.')->required(),
        ];
    }
}
