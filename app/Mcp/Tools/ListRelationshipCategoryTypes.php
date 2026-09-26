<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the kinds of relationship filed under one category, in the order they are offered.')]
#[IsReadOnly]
class ListRelationshipCategoryTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories.types';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'relationship_category' => ['required', 'string'],
        ]);

        $types = $this->relationshipCategory($request, $validated['relationship_category'])->types()
            ->with('relationshipCategory')
            ->withCount('relationships')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(RelationshipTypeResource::collection($types)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'relationship_category' => $schema->string()->description('The identifier of the category, as list-relationship-categories gives it.')->required(),
        ];
    }
}
