<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateStructureVersionSectionPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Resources\StructureVersionSectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StructureVersionSectionPositionController extends TemplateScopedController
{
    public function update(Request $request, string $template, string $version, string $section): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        try {
            new UpdateStructureVersionSectionPosition(
                user: $request->user(),
                row: $row,
                position: (int) $validated['position'],
            )->execute();
        } catch (PublishedVersionIsFrozen $e) {
            throw ValidationException::withMessages(['version' => $e->getMessage()]);
        }

        return new StructureVersionSectionResource($row->refresh())->response()->setStatusCode(200);
    }
}
