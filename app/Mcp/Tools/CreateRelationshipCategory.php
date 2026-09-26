<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateRelationshipCategory as CreateRelationshipCategoryAction;
use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a category to file kinds of relationship under, such as Sports. It is added at the end of the list. The change is written to the account journal.')]
class CreateRelationshipCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = new CreateRelationshipCategoryAction(
            user: $this->user($request),
            name: $validated['name'],
        )->execute();

        return Response::json(new RelationshipCategoryResource($category->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name of the category, such as Sports.')->required(),
        ];
    }
}
