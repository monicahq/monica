<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\KnowledgeFolderResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the Knowledge of a vault: its folders in their order, each with the identifiers and titles of its notes and lists. Read a note with get-knowledge-note and a list with get-knowledge-list.')]
#[IsReadOnly]
class ListKnowledge extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.folders';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $folders = $this->vault($request, $validated['vault'])
            ->knowledgeFolders()
            ->with(['vault', 'notes:id,uuid,knowledge_folder_id,title', 'lists:id,uuid,knowledge_folder_id,title'])
            ->get();

        return Response::json(['data' => KnowledgeFolderResource::collection($folders)->resolve()]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
        ];
    }
}
