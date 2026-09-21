<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateLifeEventCategory;
use App\Actions\DeactivateLifeEventCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventCategoryResource;
use App\Models\LifeEventCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventCategoryActivationController extends Controller
{
    public function update(Request $request, string $lifeEventCategory): JsonResponse
    {
        $lifeEventCategory = $request->user()->account->lifeEventCategories()
            ->where('uuid', $lifeEventCategory)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $lifeEventCategory);

        return new LifeEventCategoryResource($lifeEventCategory->refresh())->response()->setStatusCode(200);
    }

    private function settle(Request $request, LifeEventCategory $lifeEventCategory): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $lifeEventCategory->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateLifeEventCategory(user: $request->user(), lifeEventCategory: $lifeEventCategory)->execute();

            return;
        }

        new DeactivateLifeEventCategory(user: $request->user(), lifeEventCategory: $lifeEventCategory)->execute();
    }
}
