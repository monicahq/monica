<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateLifeEventCategoryPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LifeEventCategoryPositionController extends Controller
{
    public function update(Request $request, string $lifeEventCategory): JsonResponse
    {
        $lifeEventCategory = $request->user()->account->lifeEventCategories()
            ->where('uuid', $lifeEventCategory)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateLifeEventCategoryPosition(
            user: $request->user(),
            lifeEventCategory: $lifeEventCategory,
            position: $request->integer('position'),
        )->execute();

        return new LifeEventCategoryResource($lifeEventCategory->refresh())->response()->setStatusCode(200);
    }
}
