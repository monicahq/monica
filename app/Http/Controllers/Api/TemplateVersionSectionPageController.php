<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MoveSectionToTemplateVersionPage;
use App\Http\Resources\TemplateVersionSectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateVersionSectionPageController extends TemplateStructureController
{
    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $templateVersion = $this->findVersion($this->findTemplate($request, $template), $version);
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'page_id' => ['required', 'string'],
        ]);

        new MoveSectionToTemplateVersionPage(
            user: $request->user(),
            row: $row,
            page: $templateVersion->pages()->where('uuid', $validated['page_id'])->firstOrFail(),
        )->execute();

        return new TemplateVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }
}
