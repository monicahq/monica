<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateRelationshipCategory;
use App\Actions\DestroyRelationshipCategory;
use App\Actions\UpdateRelationshipCategory;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Exceptions\CannotDeleteARelationshipCategoryInUse;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipCategoryResource;
use App\Models\RelationshipCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class RelationshipCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $categories = $request->user()->account->relationshipCategories()
            ->withCount('types')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => RelationshipCategoryResource::collection($categories->items())->resolve(),
            'meta' => [
                'page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Request $request, string $relationshipCategory): JsonResponse
    {
        return new RelationshipCategoryResource($this->find($request, $relationshipCategory))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = new CreateRelationshipCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return new RelationshipCategoryResource($this->find($request, $category->uuid))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $relationshipCategory): JsonResponse
    {
        $category = $this->find($request, $relationshipCategory);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        try {
            new UpdateRelationshipCategory(
                user: $request->user(),
                relationshipCategory: $category,
                name: $validated['name'] ?? $category->name,
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        return new RelationshipCategoryResource($this->find($request, $relationshipCategory))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $relationshipCategory): Response
    {
        try {
            new DestroyRelationshipCategory(
                user: $request->user(),
                relationshipCategory: $this->find($request, $relationshipCategory),
            )->execute();
        } catch (CannotChangeASuppliedRelationshipCategory|CannotDeleteARelationshipCategoryInUse $e) {
            throw ValidationException::withMessages(['relationshipCategory' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): RelationshipCategory
    {
        return $request->user()->account->relationshipCategories()
            ->withCount('types')
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
