<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateRelationshipType;
use App\Actions\DeactivateRelationshipType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipTypeResource;
use App\Models\RelationshipType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelationshipTypeActivationController extends Controller
{
    public function update(Request $request, string $relationshipType): JsonResponse
    {
        $relationshipType = $request->user()->account->relationshipTypes()
            ->where('uuid', $relationshipType)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $relationshipType);

        return new RelationshipTypeResource($relationshipType->refresh()->loadCount('relationships'))->response()->setStatusCode(200);
    }

    private function settle(Request $request, RelationshipType $relationshipType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $relationshipType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateRelationshipType(user: $request->user(), relationshipType: $relationshipType)->execute();

            return;
        }

        new DeactivateRelationshipType(user: $request->user(), relationshipType: $relationshipType)->execute();
    }
}
