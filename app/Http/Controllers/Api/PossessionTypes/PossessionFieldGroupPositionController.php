<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\UpdatePossessionFieldGroupPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionFieldGroupPositionController extends Controller
{
    public function update(Request $request, string $possessionType, string $group): JsonResponse
    {
        $type = $request->user()->account->possessionTypes()->where('uuid', $possessionType)->firstOrFail();
        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdatePossessionFieldGroupPosition(
            user: $request->user(),
            group: $type->groups()->where('uuid', $group)->firstOrFail(),
            position: (int) $validated['position'],
        )->execute();

        return new PossessionTypeResource($type)->response();
    }
}
