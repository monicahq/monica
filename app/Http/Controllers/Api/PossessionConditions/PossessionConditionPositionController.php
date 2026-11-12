<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionConditions;

use App\Actions\UpdatePossessionConditionPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionConditionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionConditionPositionController extends Controller
{
    public function update(Request $request, string $possessionCondition): JsonResponse
    {
        $record = $request->user()->account->possessionConditions()->where('uuid', $possessionCondition)->firstOrFail();
        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $record = new UpdatePossessionConditionPosition(
            user: $request->user(),
            possessionCondition: $record,
            position: (int) $validated['position'],
        )->execute();

        return new PossessionConditionResource($record)->response();
    }
}
