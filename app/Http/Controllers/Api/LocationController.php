<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateLocation;
use App\Actions\DestroyLocation;
use App\Actions\UpdateLocation;
use App\Helpers\LocationTree;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Rules\SingleEmoji;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));
        $page = max(1, (int) $request->query('page', '1'));

        $tree = LocationTree::forAccount($request->user()->account_id);
        $locations = $tree->ordered();

        return response()->json([
            'data' => $locations->forPage($page, $perPage)
                ->map(fn (Location $location): array => new LocationResource($location, $tree)->resolve())
                ->values()
                ->all(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $locations->count(),
            ],
        ]);
    }

    public function show(Request $request, string $location): JsonResponse
    {
        return $this->answer($request, $this->find($request, $location), 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));

        $location = new CreateLocation(
            user: $request->user(),
            name: $validated['name'],
            parent: $this->parent($request, $validated['parent_id'] ?? null),
            emoji: $validated['emoji'] ?? null,
            address: $validated['address'] ?? null,
        )->execute();

        return $this->answer($request, $location, 201);
    }

    public function update(Request $request, string $location): JsonResponse
    {
        $record = $this->find($request, $location);
        $validated = $request->validate($this->rules($request, isCreating: false));

        try {
            new UpdateLocation(
                user: $request->user(),
                location: $record,
                name: $validated['name'] ?? $record->name,
                parent: array_key_exists('parent_id', $validated) ? $this->parent($request, $validated['parent_id']) : $record->parent,
                emoji: array_key_exists('emoji', $validated) ? $validated['emoji'] : $record->emoji,
                address: array_key_exists('address', $validated) ? $validated['address'] : $record->address,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['parent_id' => $e->getMessage()]);
        }

        return $this->answer($request, $record, 200);
    }

    public function destroy(Request $request, string $location): Response
    {
        try {
            new DestroyLocation(
                user: $request->user(),
                location: $this->find($request, $location),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['location' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request, bool $isCreating = true): array
    {
        return [
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'parent_id' => ['bail', 'nullable', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'emoji' => ['nullable', 'string', 'max:32', new SingleEmoji],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function answer(Request $request, Location $location, int $status): JsonResponse
    {
        $tree = LocationTree::forAccount($request->user()->account_id);

        return new LocationResource($location, $tree)->response()->setStatusCode($status);
    }

    private function parent(Request $request, ?string $uuid): ?Location
    {
        if ($uuid === null) {
            return null;
        }

        return $request->user()->account->locations()->where('uuid', $uuid)->firstOrFail();
    }

    private function find(Request $request, string $uuid): Location
    {
        return $request->user()->account->locations()->where('uuid', $uuid)->firstOrFail();
    }
}
