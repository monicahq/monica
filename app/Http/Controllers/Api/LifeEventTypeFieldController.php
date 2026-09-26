<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ReplaceLifeEventTypeFields;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Helpers\LifeEventTypeFieldLayout;
use App\Http\Controllers\Controller;
use App\Http\Resources\StructureVersionSectionResource;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LifeEventTypeFieldController extends Controller
{
    public function index(Request $request, string $lifeEventType): JsonResponse
    {
        $version = $this->publishedVersion($this->find($request, $lifeEventType));

        return response()->json([
            'data' => $version === null
                ? []
                : StructureVersionSectionResource::collection($version->assembly)->resolve(),
        ]);
    }

    public function update(Request $request, string $lifeEventType): JsonResponse
    {
        $type = $this->find($request, $lifeEventType);

        $request->validate(LifeEventTypeFieldLayout::rules());

        try {
            new ReplaceLifeEventTypeFields(
                user: $request->user(),
                lifeEventType: $type,
                groups: LifeEventTypeFieldLayout::groups((array) $request->input('groups', [])),
                fields: LifeEventTypeFieldLayout::fields((array) $request->input('groups', []), (array) $request->input('fields', [])),
            )->execute();
        } catch (ModelNotFoundException|AcceptedFileKindIsNotValid $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        $version = $this->publishedVersion($type->refresh());

        return response()->json([
            'data' => $version === null
                ? []
                : StructureVersionSectionResource::collection($version->assembly)->resolve(),
        ]);
    }

    private function publishedVersion(LifeEventType $lifeEventType): ?StructureVersion
    {
        return $lifeEventType->structure?->publishedVersion;
    }

    private function find(Request $request, string $uuid): LifeEventType
    {
        return $request->user()->account->lifeEventTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
