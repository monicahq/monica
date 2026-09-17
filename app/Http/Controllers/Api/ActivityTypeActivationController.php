<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateActivityType;
use App\Actions\DeactivateActivityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use App\Models\ActivityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityTypeActivationController extends Controller
{
    public function update(Request $request, string $activityType): JsonResponse
    {
        $activityType = $request->user()->account->activityTypes()
            ->where('uuid', $activityType)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $activityType);

        return new ActivityTypeResource($activityType->refresh())->response()->setStatusCode(200);
    }

    private function settle(Request $request, ActivityType $activityType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $activityType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateActivityType(user: $request->user(), activityType: $activityType)->execute();

            return;
        }

        new DeactivateActivityType(user: $request->user(), activityType: $activityType)->execute();
    }
}
