<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateKnowledgeListColumn as CreateKnowledgeListColumnAction;
use App\Http\Resources\KnowledgeListResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a column to a list of a vault\'s Knowledge, after its other columns. Every column holds text. Answers with the whole list. The change is written to the account journal.')]
class CreateKnowledgeListColumn extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.columns.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $list = $this->knowledgeList($this->vault($request, $validated['vault']), $validated['list']);

        new CreateKnowledgeListColumnAction(user: $this->user($request), list: $list, name: $validated['name'])->execute();

        return Response::json(new KnowledgeListResource($list->refresh()->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'name' => $schema->string()->description('The name of the column, such as City or Rating.')->required(),
        ];
    }
}
