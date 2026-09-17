<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateActivityTypePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityTypePositionController extends Controller
{
    public function update(Request $request, string $activityType): JsonResponse
    {
        $activityType = $request->user()->account->activityTypes()
            ->where('uuid', $activityType)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateActivityTypePosition(
            user: $request->user(),
            activityType: $activityType,
            position: $request->integer('position'),
        )->execute();

        return new ActivityTypeResource($activityType->refresh())->response()->setStatusCode(200);
    }
}
