<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateLifeEventType;
use App\Actions\DestroyLifeEventType;
use App\Actions\UpdateLifeEventType;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventTypeResource;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Section;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LifeEventTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $request->user()->account->lifeEventTypes()
            ->join('life_event_categories', 'life_event_categories.id', '=', 'life_event_types.life_event_category_id')
            ->select('life_event_types.*')
            ->with('lifeEventCategory')
            ->orderBy('life_event_categories.position')
            ->orderBy('life_event_types.position')
            ->orderBy('life_event_types.id')
            ->paginate($perPage);

        return response()->json([
            'data' => LifeEventTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }

    public function show(Request $request, string $lifeEventType): JsonResponse
    {
        return new LifeEventTypeResource($this->find($request, $lifeEventType))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules(isCreating: true));

        $lifeEventType = new CreateLifeEventType(
            user: $request->user(),
            lifeEventCategory: $this->category($request, (string) $validated['life_event_category_id']),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        return new LifeEventTypeResource($lifeEventType)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $lifeEventType): JsonResponse
    {
        $type = $this->find($request, $lifeEventType);

        $validated = $request->validate($this->rules(isCreating: false));

        new UpdateLifeEventType(
            user: $request->user(),
            lifeEventType: $type,
            lifeEventCategory: isset($validated['life_event_category_id']) ? $this->category($request, (string) $validated['life_event_category_id']) : $type->lifeEventCategory,
            name: $validated['name'] ?? $type->name,
            description: array_key_exists('description', $validated) ? $validated['description'] : $type->description,
            icon: array_key_exists('icon', $validated) ? $validated['icon'] : $type->icon,
            colour: array_key_exists('colour', $validated) ? $validated['colour'] : $type->colour,
        )->execute();

        return new LifeEventTypeResource($type->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $lifeEventType): Response
    {
        try {
            new DestroyLifeEventType(
                user: $request->user(),
                lifeEventType: $this->find($request, $lifeEventType),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['lifeEventType' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $isCreating): array
    {
        return [
            'life_event_category_id' => [$isCreating ? 'required' : 'sometimes', 'uuid'],
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ];
    }

    private function category(Request $request, string $uuid): LifeEventCategory
    {
        return $request->user()->account->lifeEventCategories()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function find(Request $request, string $uuid): LifeEventType
    {
        return $request->user()->account->lifeEventTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
