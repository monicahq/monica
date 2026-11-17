<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateEntityType;
use App\Actions\DestroyEntityType;
use App\Actions\UpdateEntityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EntityTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $entityTypes = $request->user()->account->entityTypes()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => EntityTypeResource::collection($entityTypes->items())->resolve(),
            'meta' => [
                'page' => $entityTypes->currentPage(),
                'per_page' => $entityTypes->perPage(),
                'total' => $entityTypes->total(),
            ],
        ]);
    }

    public function show(Request $request, string $entityType): JsonResponse
    {
        return new EntityTypeResource($this->find($request, $entityType))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_singular' => ['required', 'string', 'max:255'],
            'name_plural' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'icon' => ['nullable', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(EntityType::COLOURS)],
            'is_active' => ['nullable', 'boolean'],
            'slug' => [
                'nullable',
                'string',
                'max:'.EntityType::SLUG_LENGTH,
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('entity_types', 'slug')->where('account_id', $request->user()->account_id),
            ],
        ]);

        $entityType = new CreateEntityType(
            user: $request->user(),
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
            isActive: $request->boolean('is_active', true),
            slug: $validated['slug'] ?? null,
        )->execute();

        return new EntityTypeResource($entityType)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $this->find($request, $entityType);

        $validated = $request->validate([
            'name_singular' => ['sometimes', 'required', 'string', 'max:255'],
            'name_plural' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'icon' => ['sometimes', 'required', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['sometimes', 'required', 'string', Rule::in(EntityType::COLOURS)],
        ]);

        new UpdateEntityType(
            user: $request->user(),
            entityType: $entityType,
            nameSingular: $validated['name_singular'] ?? $entityType->name_singular,
            namePlural: $validated['name_plural'] ?? $entityType->name_plural,
            description: array_key_exists('description', $validated) ? $validated['description'] : $entityType->description,
            icon: $validated['icon'] ?? $entityType->icon,
            colour: $validated['colour'] ?? $entityType->colour,
        )->execute();

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $entityType): Response
    {
        try {
            new DestroyEntityType(
                user: $request->user(),
                entityType: $this->find($request, $entityType),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['entityType' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): EntityType
    {
        return $request->user()->account->entityTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
