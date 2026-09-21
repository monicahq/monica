<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateLifeEventType;
use App\Actions\DeactivateLifeEventType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventTypeResource;
use App\Models\LifeEventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventTypeActivationController extends Controller
{
    public function update(Request $request, string $lifeEventType): JsonResponse
    {
        $lifeEventType = $request->user()->account->lifeEventTypes()
            ->where('uuid', $lifeEventType)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $lifeEventType);

        return new LifeEventTypeResource($lifeEventType->refresh())->response()->setStatusCode(200);
    }

    private function settle(Request $request, LifeEventType $lifeEventType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $lifeEventType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateLifeEventType(user: $request->user(), lifeEventType: $lifeEventType)->execute();

            return;
        }

        new DeactivateLifeEventType(user: $request->user(), lifeEventType: $lifeEventType)->execute();
    }
}
