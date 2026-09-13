<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateRelationshipTypePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelationshipTypePositionController extends Controller
{
    public function update(Request $request, string $relationshipType): JsonResponse
    {
        $relationshipType = $request->user()->account->relationshipTypes()
            ->where('uuid', $relationshipType)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateRelationshipTypePosition(
            user: $request->user(),
            relationshipType: $relationshipType,
            position: $request->integer('position'),
        )->execute();

        return new RelationshipTypeResource($relationshipType->refresh()->loadCount('relationships'))->response()->setStatusCode(200);
    }
}
