<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one category of relationship types: its name, position, whether it is offered, whether it came with the account and how many types it holds.')]
#[IsReadOnly]
class GetRelationshipCategory extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
        ]);

        return Response::json(new RelationshipCategoryResource($this->relationshipCategory($request, $validated['relationship_category']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
        ];
    }
}
