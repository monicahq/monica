<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateKnowledgeList as UpdateKnowledgeListAction;
use App\Http\Resources\KnowledgeListResource;
use App\Models\KnowledgeList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a list of a vault\'s Knowledge: its title, its description, or the folder it sits in. What is not given stays as it is. Its columns and items change through their own tools. The change is written to the account journal.')]
class UpdateKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'list' => ['required', 'string'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:'.KnowledgeList::MAX_DESCRIPTION_LENGTH],
            'folder' => ['sometimes', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $list = $this->knowledgeList($vault, $validated['list']);

        new UpdateKnowledgeListAction(
            user: $this->user($request),
            list: $list,
            title: array_key_exists('title', $validated) ? (string) $validated['title'] : null,
            description: array_key_exists('description', $validated) ? (string) $validated['description'] : null,
            folder: isset($validated['folder']) ? $this->knowledgeFolder($vault, $validated['folder']) : null,
        )->execute();

        return Response::json(new KnowledgeListResource($list->refresh()->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'list' => $schema->string()->description('The identifier of the list, as list-knowledge gives it.')->required(),
            'title' => $schema->string()->description('The new title. A blank title keeps the one it has.'),
            'description' => $schema->string()->description('The new description, in Markdown. An empty one clears it.'),
            'folder' => $schema->string()->description('The identifier of the folder to move it to.'),
        ];
    }
}
