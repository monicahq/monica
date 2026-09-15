<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateFieldPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FieldPositionController extends Controller
{
    public function update(Request $request, Template $template, StructureVersionSection $structureVersionSection, Field $field): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        if ($field->section_version_id !== $structureVersionSection->section_version_id) {
            throw new ModelNotFoundException('This field is not in this section.');
        }

        try {
            $sectionVersion = new MakeSectionVersionEditable(
                user: $request->user(),
                row: $structureVersionSection,
            )->execute();

            $target = $sectionVersion->id === $field->section_version_id
                ? $field
                : $sectionVersion->fields()->where('key', $field->key)->firstOrFail();

            new UpdateFieldPosition(
                user: $request->user(),
                field: $target,
                position: (int) $validated['position'],
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has been published and cannot be changed.'));
        }

        return redirect()->route('settings.templateBuilder.index', [
            'template' => $template,
            'page' => $structureVersionSection->structureVersionPage->key,
        ]);
    }
}
