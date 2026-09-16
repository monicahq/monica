<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipCategoryResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the groups the account files its kinds of relationship under.')]
class ListRelationshipCategories extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipCategories';

    public function handle(Request $request): Response
    {
        $categories = $this->user($request)->account->relationshipCategories()
            ->withCount('types')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Response::json(RelationshipCategoryResource::collection($categories)->resolve());
    }
}
