<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTemplateVersionPagePosition;
use App\Http\Resources\TemplateVersionPageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateVersionPagePositionController extends TemplateStructureController
{
    public function update(Request $request, string $template, string $version, string $page): JsonResponse
    {
        $page = $this->findVersion($this->findTemplate($request, $template), $version)
            ->pages()
            ->where('uuid', $page)
            ->firstOrFail();

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateTemplateVersionPagePosition(
            user: $request->user(),
            page: $page,
            position: (int) $validated['position'],
        )->execute();

        return new TemplateVersionPageResource($page->refresh())->response()->setStatusCode(200);
    }
}
