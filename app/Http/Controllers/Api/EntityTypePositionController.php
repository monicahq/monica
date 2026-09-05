<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateEntityTypePosition;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityTypePositionController extends Controller
{
    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $request->user()->account->entityTypes()
            ->where('uuid', $entityType)
            ->firstOrFail();

        $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateEntityTypePosition(
            user: $request->user(),
            entityType: $entityType,
            position: $request->integer('position'),
        )->execute();

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }
}
