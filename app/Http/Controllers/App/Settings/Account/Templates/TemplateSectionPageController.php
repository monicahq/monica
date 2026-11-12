<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\MoveSectionToStructureVersionPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateSectionPageController extends Controller
{
    public function update(Request $request, Template $template, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'page' => [
                'bail',
                'required',
                'string',
                'bail',
                'uuid',
                Rule::exists('structure_version_pages', 'uuid')
                    ->where('structure_version_id', $structureVersionSection->structure_version_id),
            ],
        ]);

        $page = StructureVersionPage::query()->where('uuid', $validated['page'])->firstOrFail();

        try {
            new MoveSectionToStructureVersionPage(
                user: $request->user(),
                row: $structureVersionSection,
                page: $page,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return redirect()->route('settings.templateBuilder.index', $template)
                ->with('error', __('This version has been published and cannot be changed.'))
                ->with('error_description', __('Start a draft from it, and the pages of the draft are yours to arrange.'));
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key])
            ->with('status', __('The section is moved.'))
            ->with('status_description', __('It is now the last one on :page.', ['page' => $page->label]));
    }
}
