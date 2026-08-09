<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\AddSectionToTemplateVersion;
use App\Actions\CreateSection;
use App\Actions\RemoveSectionFromTemplateVersion;
use App\Actions\UpdateTemplateVersionSection;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The sections on one template: putting one there, retitling it, taking it off.
 *
 * Adding makes a section of its own rather than something owned by this
 * template, since a section is a reusable thing the account keeps and may put
 * on several templates later.
 *
 * Retitling changes the heading this template shows and nothing else. The
 * section keeps its own name for everybody else carrying it, which is what
 * stops a rename here from reaching into somebody else's template.
 */
class TemplateSectionController extends Controller
{
    public function create(Request $request, Template $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $version = $template->versions()->first();

        if ($version === null || ! $version->status->isEditable()) {
            return $this->refuse($template);
        }

        $section = new CreateSection(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        new AddSectionToTemplateVersion(
            user: $request->user(),
            templateVersion: $version,
            sectionVersion: $section->versions()->first(),
        )->execute();

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The section is added.'));
    }

    public function update(Request $request, Template $template, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'title_override' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateTemplateVersionSection(
                user: $request->user(),
                row: $templateVersionSection,
                titleOverride: $validated['title_override'] ?? null,
                isCollapsedByDefault: $templateVersionSection->is_collapsed_by_default,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The heading is saved.'));
    }

    public function destroy(Request $request, Template $template, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        try {
            new RemoveSectionFromTemplateVersion(
                user: $request->user(),
                row: $templateVersionSection,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('status', __('The section is off this template.'))
            ->with('status_description', __('The section itself is untouched, and every other template carrying it is unchanged.'));
    }

    private function refuse(Template $template): RedirectResponse
    {
        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('error', __('This version has been published and cannot be changed.'))
            ->with('error_description', __('Start a draft, and the published version goes on serving the records already on it.'));
    }
}
