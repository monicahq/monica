<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateKnowledgeList as CreateKnowledgeListAction;
use App\Http\Resources\KnowledgeListResource;
use App\Models\KnowledgeList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Start a list in a vault\'s Knowledge, with the columns "Name" and "Note". Rename or add columns with update-knowledge-list-column and create-knowledge-list-column, then add items with create-knowledge-list-item. Without a folder it goes to the first folder. The change is written to the account journal.')]
class CreateKnowledgeList extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.knowledge.lists.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.KnowledgeList::MAX_DESCRIPTION_LENGTH],
            'folder' => ['nullable', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $list = new CreateKnowledgeListAction(
            user: $this->user($request),
            vault: $vault,
            folder: isset($validated['folder']) ? $this->knowledgeFolder($vault, $validated['folder']) : null,
            title: $validated['title'] ?? null,
            description: $validated['description'] ?? null,
        )->execute();

        return Response::json(new KnowledgeListResource($list->load('columns', 'items.cells'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'title' => $schema->string()->description('The title of the list. "Untitled list" when left out.'),
            'description' => $schema->string()->description('What the list is for, in Markdown.'),
            'folder' => $schema->string()->description('The identifier of the folder to put it in, as list-knowledge gives it.'),
        ];
    }
}
