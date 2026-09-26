<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateRelationshipCategoryPosition;
use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a category of relationship types to another place in the list. The change is written to the account journal.')]
class SetRelationshipCategoryPosition extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.position.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $category = $this->relationshipCategory($request, $validated['relationship_category']);

        new UpdateRelationshipCategoryPosition(
            user: $this->user($request),
            relationshipCategory: $category,
            position: (int) $validated['position'],
        )->execute();

        return Response::json(new RelationshipCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
            'position' => $schema->integer()->description('Where it goes in the list of categories, counting from 1.')->required(),
        ];
    }
}
