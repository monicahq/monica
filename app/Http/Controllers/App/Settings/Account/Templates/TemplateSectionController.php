<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateSection;
use App\Actions\RemoveSectionFromStructureVersion;
use App\Actions\UpdateSection;
use App\Actions\UpdateStructureVersionSection;
use App\Enums\VaultColourEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\StructureVersionSection;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateSectionController extends Controller
{
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
                'bail',
                'nullable',
                'string',
                'bail',
                'uuid',
                Rule::exists('structure_version_pages', 'uuid')->where('structure_version_id', $version->id),
            ],
        ]);

        $page = $version->pages->firstWhere('uuid', $validated['page'] ?? null);

        $section = new CreateSection(
            user: $request->user(),
            name: $validated['name'],
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
        )->execute();

        new AddSectionToStructureVersion(
            user: $request->user(),
            structureVersion: $version,
            sectionVersion: $section->versions()->first(),
            structureVersionPage: $page,
        )->execute();

        return redirect()->route('settings.templateBuilder.index', ['template' => $template, 'page' => $page?->key])
            ->with('status', __('The section is added.'));
    }

    public function update(Request $request, Template $template, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'title_override' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(VaultColourEnum::values())],
        ]);

        if (! $structureVersionSection->structureVersion->status->isEditable()) {
            return $this->refuse($template);
        }

        try {
            new UpdateStructureVersionSection(
                user: $request->user(),
                row: $structureVersionSection,
                titleOverride: $validated['title_override'] ?? null,
                isCollapsedByDefault: $structureVersionSection->is_collapsed_by_default,
            )->execute();

            $section = $structureVersionSection->sectionVersion->section;

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
            'page' => $structureVersionSection->structureVersionPage->key,
        ])->with('status', __('The section is saved.'));
    }

    public function destroy(Request $request, Template $template, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $page = $structureVersionSection->structureVersionPage->key;

        try {
            new RemoveSectionFromStructureVersion(
                user: $request->user(),
                row: $structureVersionSection,
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
            ->with('error_description', __('Start a draft, and the published version keeps serving the records already on it.'));
    }
}
