<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\CreatePossessionFieldGroup;
use App\Actions\DestroyPossessionFieldGroup;
use App\Actions\UpdatePossessionFieldGroup;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PossessionFieldGroupController extends Controller
{
    public function create(Request $request, string $possessionType): JsonResponse
    {
        $type = $this->type($request, $possessionType);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new CreatePossessionFieldGroup(
            user: $request->user(),
            possessionType: $type,
            name: $validated['name'],
        )->execute();

        return new PossessionTypeResource($type)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $possessionType, string $group): JsonResponse
    {
        $type = $this->type($request, $possessionType);
        $record = $this->group($type, $group);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        new UpdatePossessionFieldGroup(
            user: $request->user(),
            group: $record,
            name: $validated['name'] ?? $record->name,
        )->execute();

        return new PossessionTypeResource($type)->response();
    }

    public function destroy(Request $request, string $possessionType, string $group): Response
    {
        $type = $this->type($request, $possessionType);

        new DestroyPossessionFieldGroup(
            user: $request->user(),
            group: $this->group($type, $group),
        )->execute();

        return response()->noContent(204);
    }

    private function type(Request $request, string $uuid): PossessionType
    {
        return $request->user()->account->possessionTypes()->where('uuid', $uuid)->firstOrFail();
    }

    private function group(PossessionType $type, string $uuid): PossessionFieldGroup
    {
        return $type->groups()->where('uuid', $uuid)->firstOrFail();
    }
}
