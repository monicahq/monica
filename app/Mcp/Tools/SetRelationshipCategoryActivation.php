<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ActivateRelationshipCategory;
use App\Actions\DeactivateRelationshipCategory;
use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Offer a category of relationship types when contacts are linked, or stop offering it. Relationships already written stay. The change is written to the account journal.')]
class SetRelationshipCategoryActivation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.activation.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category = $this->relationshipCategory($request, $validated['relationship_category']);

        if ((bool) $validated['is_active'] === $category->is_active) {
            return Response::json(new RelationshipCategoryResource($category)->resolve());
        }

        if ($validated['is_active']) {
            new ActivateRelationshipCategory(user: $this->user($request), relationshipCategory: $category)->execute();
        }

        if (! $validated['is_active']) {
            new DeactivateRelationshipCategory(user: $this->user($request), relationshipCategory: $category)->execute();
        }

        return Response::json(new RelationshipCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
            'is_active' => $schema->boolean()->description('True to offer it, false to stop offering it.')->required(),
        ];
    }
}
