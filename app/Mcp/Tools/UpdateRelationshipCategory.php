<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateRelationshipCategory as UpdateRelationshipCategoryAction;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Rename a category the account created. One that came with the account keeps its name. The change is written to the account journal.')]
class UpdateRelationshipCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = $this->relationshipCategory($request, $validated['relationship_category']);

        try {
            new UpdateRelationshipCategoryAction(
                user: $this->user($request),
                relationshipCategory: $category,
                name: $validated['name'],
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory) {
            return Response::error(__('The category came with your account and keeps its name.'));
        }

        return Response::json(new RelationshipCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
            'name' => $schema->string()->description('The new name.')->required(),
        ];
    }
}
