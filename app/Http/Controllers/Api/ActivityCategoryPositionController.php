<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateActivityCategoryPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityCategoryPositionController extends Controller
{
    public function update(Request $request, string $activityCategory): JsonResponse
    {
        $activityCategory = $request->user()->account->activityCategories()
            ->where('uuid', $activityCategory)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateActivityCategoryPosition(
            user: $request->user(),
            activityCategory: $activityCategory,
            position: $request->integer('position'),
        )->execute();

        return new ActivityCategoryResource($activityCategory->refresh())->response()->setStatusCode(200);
    }
}
