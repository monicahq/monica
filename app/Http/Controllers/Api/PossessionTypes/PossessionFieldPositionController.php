<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\UpdatePossessionFieldPosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PossessionFieldPositionController extends Controller
{
    public function update(Request $request, string $possessionType, string $field): JsonResponse
    {
        $type = $request->user()->account->possessionTypes()->where('uuid', $possessionType)->firstOrFail();
        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdatePossessionFieldPosition(
            user: $request->user(),
            field: $type->fields()->where('uuid', $field)->firstOrFail(),
            position: (int) $validated['position'],
        )->execute();

        return new PossessionTypeResource($type)->response();
    }
}
