<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateFieldPosition;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where a field sits inside its section. Like every other write in the builder,
 * it goes through the section being made editable first, and the field is found
 * again by key when that meant copying the section.
 */
class FieldPositionController extends Controller
{
    public function update(Request $request, Template $template, TemplateVersionSection $templateVersionSection, Field $field): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:1'],
        ]);

        if ($field->section_version_id !== $templateVersionSection->section_version_id) {
            throw new ModelNotFoundException('This field is not in this section.');
        }

        try {
            $sectionVersion = new MakeSectionVersionEditable(
                user: $request->user(),
                row: $templateVersionSection,
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
            'page' => $templateVersionSection->templateVersionPage->key,
        ]);
    }
}
