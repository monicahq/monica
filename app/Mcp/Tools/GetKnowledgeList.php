<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\KnowledgeListResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one list of a vault\'s Knowledge: its title, its description, its columns in order, and its items, each holding its texts keyed by the identifier of their column. A blank cell is left out.')]
#[IsReadOnly]
class GetKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        return Response::json(new KnowledgeListResource($list->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
        ];
    }
}
