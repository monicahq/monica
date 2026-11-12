<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionConditions;

use App\Actions\CreatePossessionCondition;
use App\Actions\DestroyPossessionCondition;
use App\Actions\UpdatePossessionCondition;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionConditionResource;
use App\Models\PossessionCondition;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionConditionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));
        $page = max(1, (int) $request->query('page', '1'));

        $possessionConditions = $request->user()->account->possessionConditions()->get();

        return response()->json([
            'data' => $possessionConditions->forPage($page, $perPage)
                ->map(fn (PossessionCondition $possessionCondition): array => new PossessionConditionResource($possessionCondition)->resolve())
                ->values()
                ->all(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $possessionConditions->count(),
            ],
        ]);
    }

    public function show(Request $request, string $possessionCondition): JsonResponse
    {
        return new PossessionConditionResource($this->find($request, $possessionCondition))->response();
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        $possessionCondition = new CreatePossessionCondition(
            user: $request->user(),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : VaultColourEnum::Blue,
            icon: $validated['icon'] ?? null,
        )->execute();

        return new PossessionConditionResource($possessionCondition)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $possessionCondition): JsonResponse
    {
        $record = $this->find($request, $possessionCondition);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        new UpdatePossessionCondition(
            user: $request->user(),
            possessionCondition: $record,
            name: $validated['name'],
            colour: VaultColourEnum::from($validated['colour']),
            icon: $validated['icon'] ?? null,
        )->execute();

        return new PossessionConditionResource($record)->response();
    }

    public function destroy(Request $request, string $possessionCondition): Response
    {
        new DestroyPossessionCondition(
            user: $request->user(),
            possessionCondition: $this->find($request, $possessionCondition),
        )->execute();

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): PossessionCondition
    {
        return $request->user()->account->possessionConditions()->where('uuid', $uuid)->firstOrFail();
    }
}
