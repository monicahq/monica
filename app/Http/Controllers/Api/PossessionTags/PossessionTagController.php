<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PossessionTags;

use App\Actions\CreatePossessionTag;
use App\Actions\DestroyPossessionTag;
use App\Actions\UpdatePossessionTag;
use App\Enums\VaultColourEnum;
use App\Helpers\LocaleCollation;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTagResource;
use App\Models\PossessionTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionTagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));
        $page = max(1, (int) $request->query('page', '1'));

        $possessionTags = LocaleCollation::sortByCollator($request->user()->account->possessionTags()->get(), 'name');

        return response()->json([
            'data' => $possessionTags->forPage($page, $perPage)
                ->map(fn (PossessionTag $possessionTag): array => new PossessionTagResource($possessionTag)->resolve())
                ->values()
                ->all(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $possessionTags->count(),
            ],
        ]);
    }

    public function show(Request $request, string $possessionTag): JsonResponse
    {
        return new PossessionTagResource($this->find($request, $possessionTag))->response();
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['nullable', Rule::enum(VaultColourEnum::class)],
        ]);

        $possessionTag = new CreatePossessionTag(
            user: $request->user(),
            name: $validated['name'],
            colour: isset($validated['colour']) ? VaultColourEnum::from($validated['colour']) : VaultColourEnum::Blue,
        )->execute();

        return new PossessionTagResource($possessionTag)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $possessionTag): JsonResponse
    {
        $record = $this->find($request, $possessionTag);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'colour' => ['sometimes', Rule::enum(VaultColourEnum::class)],
        ]);

        new UpdatePossessionTag(
            user: $request->user(),
            possessionTag: $record,
            name: $validated['name'] ?? $record->name,
            colour: VaultColourEnum::from($validated['colour'] ?? $record->colour),
        )->execute();

        return new PossessionTagResource($record)->response();
    }

    public function destroy(Request $request, string $possessionTag): Response
    {
        new DestroyPossessionTag(
            user: $request->user(),
            possessionTag: $this->find($request, $possessionTag),
        )->execute();

        return response()->noContent(204);
    }

    private function find(Request $request, string $uuid): PossessionTag
    {
        return $request->user()->account->possessionTags()->where('uuid', $uuid)->firstOrFail();
    }
}
