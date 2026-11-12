<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTypes;

use App\Actions\CreatePossessionType;
use App\Actions\DestroyPossessionType;
use App\Actions\UpdatePossessionType;
use App\Enums\VaultColourEnum;
use App\Helpers\LocaleCollation;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTypeResource;
use App\Models\PossessionType;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));
        $page = max(1, (int) $request->query('page', '1'));

        $possessionTypes = LocaleCollation::sortByCollator($request->user()->account->possessionTypes()->with(['groups.fields', 'standaloneFields'])->get(), 'name');

        return response()->json([
            'data' => $possessionTypes->forPage($page, $perPage)
                ->map(fn (PossessionType $possessionType): array => new PossessionTypeResource($possessionType)->resolve())
                ->values()
                ->all(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $possessionTypes->count(),
            ],
        ]);
    }

    public function show(Request $request, string $possessionType): JsonResponse
    {
        return new PossessionTypeResource($this->find($request, $possessionType))->response();
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        $possessionType = new CreatePossessionType(
            user: $request->user(),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : null,
            icon: $validated['icon'] ?? null,
        )->execute();

        return new PossessionTypeResource($possessionType)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $possessionType): JsonResponse
    {
        $record = $this->find($request, $possessionType);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        new UpdatePossessionType(
            user: $request->user(),
            possessionType: $record,
            name: $validated['name'],
            colour: VaultColourEnum::from($validated['colour']),
            icon: $validated['icon'] ?? null,
        )->execute();

        return new PossessionTypeResource($record)->response();
    }

    public function destroy(Request $request, string $possessionType): Response
    {
        new DestroyPossessionType(
            user: $request->user(),
            possessionType: $this->find($request, $possessionType),
        )->execute();

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): PossessionType
    {
        return $request->user()->account->possessionTypes()->where('uuid', $uuid)->firstOrFail();
    }
}
