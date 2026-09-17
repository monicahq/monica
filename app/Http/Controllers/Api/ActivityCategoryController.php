<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateActivityCategory;
use App\Actions\DestroyActivityCategory;
use App\Actions\UpdateActivityCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityCategoryResource;
use App\Models\ActivityCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ActivityCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $categories = $request->user()->account->activityCategories()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ActivityCategoryResource::collection($categories->items())->resolve(),
            'meta' => [
                'page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function show(Request $request, string $activityCategory): JsonResponse
    {
        return new ActivityCategoryResource($this->find($request, $activityCategory))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = new CreateActivityCategory(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return new ActivityCategoryResource($category)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $activityCategory): JsonResponse
    {
        $category = $this->find($request, $activityCategory);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdateActivityCategory(
            user: $request->user(),
            activityCategory: $category,
            name: $validated['name'],
        )->execute();

        return new ActivityCategoryResource($category->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $activityCategory): Response
    {
        try {
            new DestroyActivityCategory(
                user: $request->user(),
                activityCategory: $this->find($request, $activityCategory),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['activityCategory' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): ActivityCategory
    {
        return $request->user()->account->activityCategories()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
