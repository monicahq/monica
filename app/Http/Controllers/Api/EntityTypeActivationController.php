<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ActivateEntityType;
use App\Actions\DeactivateEntityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityTypeActivationController extends Controller
{
    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $request->user()->account->entityTypes()
            ->where('uuid', $entityType)
            ->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->settle($request, $entityType);

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }

    private function settle(Request $request, EntityType $entityType): void
    {
        $wanted = $request->boolean('is_active');

        if ($wanted === $entityType->is_active) {
            return;
        }

        if ($wanted) {
            new ActivateEntityType(user: $request->user(), entityType: $entityType)->execute();

            return;
        }

        new DeactivateEntityType(user: $request->user(), entityType: $entityType)->execute();
    }
}
