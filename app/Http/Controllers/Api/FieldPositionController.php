<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateFieldPosition;
use App\Http\Resources\FieldResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldPositionController extends TemplateScopedController
{
    public function update(Request $request, string $template, string $version, string $section, string $field): JsonResponse
    {
        $row = $this->findRow($request, $template, $version, $section);
        $key = $row->sectionVersion->fields()->whereKey($field)->firstOrFail()->key;

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $sectionVersion = new MakeSectionVersionEditable(user: $request->user(), row: $row)->execute();
        $field = $sectionVersion->fields()->where('key', $key)->firstOrFail();

        new UpdateFieldPosition(
            user: $request->user(),
            field: $field,
            position: (int) $validated['position'],
        )->execute();

        return new FieldResource($field->refresh())->response()->setStatusCode(200);
    }
}
