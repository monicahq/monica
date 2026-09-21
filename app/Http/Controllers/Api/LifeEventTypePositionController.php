<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateLifeEventTypePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventTypePositionController extends Controller
{
    public function update(Request $request, string $lifeEventType): JsonResponse
    {
        $lifeEventType = $request->user()->account->lifeEventTypes()
            ->where('uuid', $lifeEventType)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateLifeEventTypePosition(
            user: $request->user(),
            lifeEventType: $lifeEventType,
            position: $request->integer('position'),
        )->execute();

        return new LifeEventTypeResource($lifeEventType->refresh())->response()->setStatusCode(200);
    }
}
