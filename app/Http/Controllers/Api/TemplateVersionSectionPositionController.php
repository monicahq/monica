<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTemplateVersionSectionPosition;
use App\Http\Resources\TemplateVersionSectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateVersionSectionPositionController extends TemplateStructureController
{
    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateTemplateVersionSectionPosition(
            user: $request->user(),
            row: $row,
            position: (int) $validated['position'],
        )->execute();

        return new TemplateVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }
}
