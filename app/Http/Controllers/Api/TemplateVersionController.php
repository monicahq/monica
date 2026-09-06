<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateTemplateVersionDraft;
use App\Actions\DestroyTemplateVersion;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateVersionResource;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TemplateVersionController extends Controller
{
    public function index(Request $request, string $template): JsonResponse
    {
        $template = $this->findTemplate($request, $template);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $versions = $template->versions()
            ->with(['template', 'previousVersion'])
            ->paginate($perPage);

        return response()->json([
            'data' => TemplateVersionResource::collection($versions->items())->resolve(),
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

        return new TemplateVersionResource($this->findVersion($template, $version))
            ->withStructure()
            ->response()
            ->setStatusCode(200);
    }

    public function create(Request $request, string $template): JsonResponse
    {
        $template = $this->findTemplate($request, $template);

        $startingPoint = $template->publishedVersion ?? $template->versions()->first();

        if ($startingPoint === null) {
            throw new ModelNotFoundException('This template has no version to start from.');
        }

        $open = $template->versions()->where('status', VersionStatusEnum::Draft)->exists();

        $draft = new CreateTemplateVersionDraft(
            user: $request->user(),
            templateVersion: $startingPoint,
        )->execute();

        return new TemplateVersionResource($draft->load(['template', 'previousVersion']))
            ->response()
            ->setStatusCode($open ? 200 : 201);
    }

    public function destroy(Request $request, string $template, string $version): Response
    {
        $template = $this->findTemplate($request, $template);

        try {
            new DestroyTemplateVersion(
                user: $request->user(),
                templateVersion: $this->findVersion($template, $version),
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

    private function findVersion(Template $template, string $versionNumber): TemplateVersion
    {
        return $template->versions()
            ->with([
                'template',
                'previousVersion',
                'pages.assembly.sectionVersion.section',
                'pages.assembly.sectionVersion.fields',
            ])
            ->where('version_number', $versionNumber)
            ->firstOrFail();
    }
}
