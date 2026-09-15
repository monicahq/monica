<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MoveSectionToStructureVersionPage;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureVersionSectionPageController extends TemplateScopedController
{
    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $structureVersion = $this->findVersion($this->findTemplate($request, $template), $version);
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'page_id' => ['required', 'string'],
        ]);

        new MoveSectionToStructureVersionPage(
            user: $request->user(),
            row: $row,
            page: $structureVersion->pages()->where('uuid', $validated['page_id'])->firstOrFail(),
        )->execute();

        return new StructureVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }
}
