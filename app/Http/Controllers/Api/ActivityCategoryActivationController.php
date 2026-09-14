<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateActivityCategory;
use App\Actions\DeactivateActivityCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityCategoryResource;
use App\Models\ActivityCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityCategoryActivationController extends Controller
{
    public function update(Request $request, string $activityCategory): JsonResponse
    {
        $activityCategory = $request->user()->account->activityCategories()
            ->where('uuid', $activityCategory)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $activityCategory);

        return new ActivityCategoryResource($activityCategory->refresh())->response()->setStatusCode(200);
    }

    private function settle(Request $request, ActivityCategory $activityCategory): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $activityCategory->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateActivityCategory(user: $request->user(), activityCategory: $activityCategory)->execute();

            return;
        }

        new DeactivateActivityCategory(user: $request->user(), activityCategory: $activityCategory)->execute();
    }
}
