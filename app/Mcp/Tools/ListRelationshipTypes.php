<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\RelationshipTypeResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the kinds of relationship the account has defined, such as parent or colleague.')]
#[IsReadOnly]
class ListRelationshipTypes extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.relationshipTypes';

    public function handle(Request $request): Response
    {
        $types = $this->user($request)->account->relationshipTypes()
            ->select('relationship_types.*')
            ->join('relationship_categories', 'relationship_categories.id', '=', 'relationship_types.relationship_category_id')
            ->with('relationshipCategory')
            ->withCount('relationships')
            ->orderBy('relationship_categories.position')
            ->orderBy('relationship_types.position')
            ->orderBy('relationship_types.id')
            ->get();

        return Response::json(RelationshipTypeResource::collection($types)->resolve());
    }
}
