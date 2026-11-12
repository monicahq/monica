<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\KnowledgeSearch;
use App\Http\Resources\KnowledgeListResource;
use App\Http\Resources\KnowledgeNoteResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Find the notes and lists of a vault\'s Knowledge holding every word given, in their title, their text, their description or any cell. Case, accents and a typing mistake are ignored.')]
#[IsReadOnly]
class SearchKnowledge extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.search';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $found = KnowledgeSearch::in($this->vault($request, $validated['vault']), $validated['query']);

        return Response::json(['data' => [
            ...KnowledgeNoteResource::collection($found['notes'])->resolve(),
            ...KnowledgeListResource::collection($found['lists'])->resolve(),
        ]]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'query' => $schema->string()->description('The words to find, at least two letters or digits.')->required(),
        ];
    }
}
