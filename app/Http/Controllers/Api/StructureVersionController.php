<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateStructureVersionDraft;
use App\Actions\DestroyStructureVersion;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Http\Resources\StructureVersionResource;
use App\Models\StructureVersion;
use App\Models\Template;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class StructureVersionController extends Controller
{
    public function index(Request $request, string $template): JsonResponse
    {
        $template = $this->findTemplate($request, $template);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $versions = $template->versions()
            ->with(['structure.owner', 'previousVersion'])
            ->paginate($perPage);

        return response()->json([
            'data' => StructureVersionResource::collection($versions->items())->resolve(),
            'meta' => [
                'page' => $versions->currentPage(),
                'per_page' => $versions->perPage(),
                'total' => $versions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $template, string $version): JsonResponse
    {
        $template = $this->findTemplate($request, $template);

        return new StructureVersionResource($this->findVersion($template, $version))
            ->withStructure()
            ->response()
            ->setStatusCode(200);
    }

    public function create(Request $request, string $template): JsonResponse
    {
        $template = $this->findTemplate($request, $template);

        $startingPoint = $template->structure->publishedVersion ?? $template->versions()->first();

        if ($startingPoint === null) {
            throw new ModelNotFoundException('This template has no version to start from.');
        }

        $open = $template->versions()->where('status', VersionStatusEnum::Draft)->exists();

        $draft = new CreateStructureVersionDraft(
            user: $request->user(),
            structureVersion: $startingPoint,
        )->execute();

        return new StructureVersionResource($draft->load(['structure.owner', 'previousVersion']))
            ->response()
            ->setStatusCode($open ? 200 : 201);
    }

    public function destroy(Request $request, string $template, string $version): Response
    {
        $template = $this->findTemplate($request, $template);

        try {
            new DestroyStructureVersion(
                user: $request->user(),
                structureVersion: $this->findVersion($template, $version),
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function findTemplate(Request $request, string $uuid): Template
    {
        return $request->user()->account->templates()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function findVersion(Template $template, string $versionNumber): StructureVersion
    {
        return $template->versions()
            ->with([
                'structure.owner',
                'previousVersion',
                'pages.assembly.sectionVersion.section',
                'pages.assembly.sectionVersion.fields',
            ])
            ->where('version_number', $versionNumber)
            ->firstOrFail();
    }
}
