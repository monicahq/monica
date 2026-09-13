<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateRelationshipCategory;
use App\Actions\DeactivateRelationshipCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipCategoryResource;
use App\Models\RelationshipCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelationshipCategoryActivationController extends Controller
{
    public function update(Request $request, string $relationshipCategory): JsonResponse
    {
        $relationshipCategory = $request->user()->account->relationshipCategories()
            ->where('uuid', $relationshipCategory)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $relationshipCategory);

        return new RelationshipCategoryResource($relationshipCategory->refresh()->loadCount('types'))->response()->setStatusCode(200);
    }

    private function settle(Request $request, RelationshipCategory $relationshipCategory): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $relationshipCategory->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateRelationshipCategory(user: $request->user(), relationshipCategory: $relationshipCategory)->execute();

            return;
        }

        new DeactivateRelationshipCategory(user: $request->user(), relationshipCategory: $relationshipCategory)->execute();
    }
}
