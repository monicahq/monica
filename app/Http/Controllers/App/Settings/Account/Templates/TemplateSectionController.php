<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\AddSectionToTemplateVersion;
use App\Actions\CreateSection;
use App\Actions\RemoveSectionFromTemplateVersion;
use App\Actions\UpdateSection;
use App\Actions\UpdateTemplateVersionSection;
use App\Enums\VaultColourEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The sections on one template: putting one there, retitling it, taking it
 * off.
 *
 * Adding makes a section of its own rather than something this template owns,
 * since a section is reusable and may end up on several. Retitling changes
 * the heading this template shows and nothing else, which is what stops a
 * rename here from reaching into somebody else's template.
 */
class TemplateSectionController extends Controller
{
    /**
     * The section lands on the page the builder had open. Nobody chooses it
     * separately: the tab somebody is looking at is the tab they mean.
     */
    public function create(Request $request, Template $template): RedirectResponse
    {
        $version = $template->versions()->first();

        if ($version === null || ! $version->status->isEditable()) {
            return $this->refuse($template);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
            'page' => [
                'nullable',
                'string',
                Rule::exists('template_version_pages', 'uuid')->where('template_version_id', $version->id),
            ],
        ]);

        $page = $version->pages->firstWhere('uuid', $validated['page'] ?? null);

        $section = new CreateSection(
            user: $request->user(),
            name: $validated['name'],
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        new AddSectionToTemplateVersion(
            user: $request->user(),
            templateVersion: $version,
            sectionVersion: $section->versions()->first(),
            templateVersionPage: $page,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page?->key])
            ->with('status', __('The section is added.'));
    }

    /**
     * The heading and the mark are saved together and stored apart. The heading
     * belongs to this placement, so changing it reaches no other template; the
     * mark belongs to the section, so changing it reaches all of them. One form
     * asks for both because they are one gesture to whoever is looking at the
     * row, and the screen says which is which.
     */
    public function update(Request $request, Template $template, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'title_override' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ]);

        /*
         * The mark sits on the section, so writing it never touches the pivot
         * and never trips the guard that model keeps. Asking here is what stops
         * a published version being changed through the one half of this form
         * that would otherwise reach past it.
         */
        if (! $templateVersionSection->templateVersion->status->isEditable()) {
            return $this->refuse($template);
        }

        try {
            new UpdateTemplateVersionSection(
                user: $request->user(),
                row: $templateVersionSection,
                titleOverride: $validated['title_override'] ?? null,
                isCollapsedByDefault: $templateVersionSection->is_collapsed_by_default,
            )->execute();

            $section = $templateVersionSection->sectionVersion->section;

            new UpdateSection(
                user: $request->user(),
                section: $section,
                name: $section->getRawOriginal('name') ?? $section->name,
                description: $section->description,
                icon: $validated['icon'] ?? null,
                colour: $validated['colour'] ?? null,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', [
            'template' => $template,
            'page' => $templateVersionSection->templateVersionPage->key,
        ])->with('status', __('The section is saved.'));
    }

    public function destroy(Request $request, Template $template, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $page = $templateVersionSection->templateVersionPage->key;

        try {
            new RemoveSectionFromTemplateVersion(
                user: $request->user(),
                row: $templateVersionSection,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page])
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
