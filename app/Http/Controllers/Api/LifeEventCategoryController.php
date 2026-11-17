<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateLifeEventCategory;
use App\Actions\DestroyLifeEventCategory;
use App\Actions\UpdateLifeEventCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventCategoryResource;
use App\Models\LifeEventCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LifeEventCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $categories = $request->user()->account->lifeEventCategories()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => LifeEventCategoryResource::collection($categories->items())->resolve(),
            'meta' => [
                'page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Request $request, string $lifeEventCategory): JsonResponse
    {
        return new LifeEventCategoryResource($this->find($request, $lifeEventCategory))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = new CreateLifeEventCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return new LifeEventCategoryResource($category)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $lifeEventCategory): JsonResponse
    {
        $category = $this->find($request, $lifeEventCategory);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        new UpdateLifeEventCategory(
            user: $request->user(),
            lifeEventCategory: $category,
            name: $validated['name'] ?? $category->name,
        )->execute();

        return new LifeEventCategoryResource($category->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $lifeEventCategory): Response
    {
        try {
            new DestroyLifeEventCategory(
                user: $request->user(),
                lifeEventCategory: $this->find($request, $lifeEventCategory),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['lifeEventCategory' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): LifeEventCategory
    {
        return $request->user()->account->lifeEventCategories()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
