<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateRelationshipCategoryPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelationshipCategoryPositionController extends Controller
{
    public function update(Request $request, string $relationshipCategory): JsonResponse
    {
        $relationshipCategory = $request->user()->account->relationshipCategories()
            ->where('uuid', $relationshipCategory)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateRelationshipCategoryPosition(
            user: $request->user(),
            relationshipCategory: $relationshipCategory,
            position: $request->integer('position'),
        )->execute();

        return new RelationshipCategoryResource($relationshipCategory->refresh()->loadCount('types'))->response()->setStatusCode(200);
    }
}
