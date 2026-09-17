<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateActivityType;
use App\Actions\DestroyActivityType;
use App\Actions\UpdateActivityType;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityTypeResource;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\Section;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $request->user()->account->activityTypes()
            ->join('activity_categories', 'activity_categories.id', '=', 'activity_types.activity_category_id')
            ->select('activity_types.*')
            ->with('activityCategory')
            ->orderBy('activity_categories.position')
            ->orderBy('activity_types.position')
            ->orderBy('activity_types.id')
            ->paginate($perPage);

        return response()->json([
            'data' => ActivityTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }

    public function show(Request $request, string $activityType): JsonResponse
    {
        return new ActivityTypeResource($this->find($request, $activityType))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $activityType = new CreateActivityType(
            user: $request->user(),
            activityCategory: $this->category($request, (string) $validated['activity_category_id']),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return new ActivityTypeResource($activityType)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $activityType): JsonResponse
    {
        $type = $this->find($request, $activityType);

        $validated = $request->validate($this->rules());

        new UpdateActivityType(
            user: $request->user(),
            activityType: $type,
            activityCategory: $this->category($request, (string) $validated['activity_category_id']),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return new ActivityTypeResource($type->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $activityType): Response
    {
        try {
            new DestroyActivityType(
                user: $request->user(),
                activityType: $this->find($request, $activityType),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['activityType' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'activity_category_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ];
    }

    private function category(Request $request, string $uuid): ActivityCategory
    {
        return $request->user()->account->activityCategories()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function find(Request $request, string $uuid): ActivityType
    {
        return $request->user()->account->activityTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
