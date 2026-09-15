<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateStructureVersionPagePosition;
use App\Http\Resources\StructureVersionPageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureVersionPagePositionController extends TemplateScopedController
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

        new UpdateStructureVersionPagePosition(
            user: $request->user(),
            page: $page,
            position: (int) $validated['position'],
        )->execute();

        return new StructureVersionPageResource($page->refresh())->response()->setStatusCode(200);
    }
}
