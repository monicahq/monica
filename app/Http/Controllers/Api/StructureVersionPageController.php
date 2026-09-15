<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateStructureVersionPage;
use App\Actions\DestroyStructureVersionPage;
use App\Actions\UpdateStructureVersionPage;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Http\Resources\StructureVersionPageResource;
use App\Models\StructureVersionPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class StructureVersionPageController extends TemplateScopedController
{
    public function create(Request $request, string $template, string $version): JsonResponse
    {
        $structureVersion = $this->findVersion($this->findTemplate($request, $template), $version);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $page = new CreateStructureVersionPage(
                user: $request->user(),
                structureVersion: $structureVersion,
                label: $validated['label'],
                key: $validated['key'] ?? null,
            )->execute();
        } catch (PageLabelIsAlreadyUsed $e) {
            throw ValidationException::withMessages(['label' => $e->getMessage()]);
        }

        return new StructureVersionPageResource($page)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $template, string $version, string $page): JsonResponse
    {
        $page = $this->findPage($request, $template, $version, $page);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        try {
            new UpdateStructureVersionPage(
                user: $request->user(),
                page: $page,
                label: $validated['label'],
            )->execute();
        } catch (PageLabelIsAlreadyUsed $e) {
            throw ValidationException::withMessages(['label' => $e->getMessage()]);
        }

        return new StructureVersionPageResource($page->refresh())->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $template, string $version, string $page): Response
    {
        try {
            new DestroyStructureVersionPage(
                user: $request->user(),
                page: $this->findPage($request, $template, $version, $page),
            )->execute();
        } catch (CannotDeleteAPageInUse|CannotDeleteTheLastPage $e) {
            throw ValidationException::withMessages(['page' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    protected function findPage(Request $request, string $template, string $version, string $uuid): StructureVersionPage
    {
        return $this->findVersion($this->findTemplate($request, $template), $version)
            ->pages()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
