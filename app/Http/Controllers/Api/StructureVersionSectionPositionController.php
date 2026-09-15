<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateStructureVersionSectionPosition;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StructureVersionSectionPositionController extends TemplateScopedController
{
    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        new UpdateStructureVersionSectionPosition(
            user: $request->user(),
            row: $row,
            position: (int) $validated['position'],
        )->execute();

        return new StructureVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }
}
