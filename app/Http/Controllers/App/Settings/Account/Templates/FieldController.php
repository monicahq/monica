<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateField;
use App\Actions\DestroyField;
use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateField;
use App\Enums\FieldTypeEnum;
use App\Enums\RepetitionOrderEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersionSection;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The fields inside one section of one template.
 *
 * Every write goes through the section being made editable first. A section
 * that is published, or shared with another template, gets a new version and
 * this template starts pointing at the copy. The field the URL names then
 * belongs to the version left behind, so it is found again by key, which is
 * what survives the copy.
 */
class FieldController extends Controller
{
    public function new(Request $request, Template $template, TemplateVersionSection $templateVersionSection): View
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        return view('app.settings.account.templates.field-type', [
            'template' => $template,
            'row' => $templateVersionSection,
            'sectionName' => $templateVersionSection->title_override ?? $templateVersionSection->sectionVersion->section->name,
            'catalogue' => new FieldCatalogueViewModel,
            'valueLists' => ValueList::query()
                ->where('account_id', $request->user()->account_id)
                ->orderBy('position')
                ->get(),
            'user' => $request->user(),
        ]);
    }

    public function create(Request $request, Template $template, TemplateVersionSection $templateVersionSection): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(FieldTypeEnum::class)],
            'label' => ['required', 'string', 'max:255'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $request->user()->account_id),
            ],
        ]);

        $type = FieldTypeEnum::from($validated['type']);

        try {
            $sectionVersion = new MakeSectionVersionEditable(
                user: $request->user(),
                row: $templateVersionSection,
            )->execute();

            $field = new CreateField(
                user: $request->user(),
                sectionVersion: $sectionVersion,
                type: $type,
                label: $validated['label'],
                valueList: $this->valueList($request, $type),
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        } catch (InvalidArgumentException) {
            return redirect()->route('settings.fields.new', [$template, $templateVersionSection])
                ->with('error', __('A field that offers choices has to say which list they come from.'))
                ->with('error_description', __('Make the list on the default values screen first, then come back.'));
        }

        return redirect()->route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $templateVersionSection->templateVersionPage->key,
            'field' => $field->id,
        ])->with('status', __('The field is added.'));
    }

    public function update(Request $request, Template $template, TemplateVersionSection $templateVersionSection, Field $field): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_highlighted' => ['nullable', 'boolean'],
            'repetition_order' => ['nullable', 'string', Rule::in(RepetitionOrderEnum::values())],
        ]);

        try {
            $target = $this->editable($request, $templateVersionSection, $field);

            new UpdateField(
                user: $request->user(),
                field: $target,
                label: $validated['label'],
                helpText: $validated['help_text'] ?? null,
                placeholder: $validated['placeholder'] ?? null,
                isRequired: $request->boolean('is_required'),
                isSensitive: $request->boolean('is_sensitive'),
                isHighlighted: $request->boolean('is_highlighted'),
                /* Sent by the panel whenever the field is a repeater, so a save
                   that changes a label cannot quietly take the direction off. */
                repetitionOrder: RepetitionOrderEnum::tryFrom((string) ($validated['repetition_order'] ?? '')),
                valueList: $target->valueList,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $templateVersionSection->templateVersionPage->key,
            'field' => $target->getKey(),
        ])->with('status', __('The field is saved.'));
    }

    public function destroy(Request $request, Template $template, TemplateVersionSection $templateVersionSection, Field $field): RedirectResponse
    {
        abort_unless($templateVersionSection->isReachableThrough($request->user(), $template), 404);

        try {
            $target = $this->editable($request, $templateVersionSection, $field);

            new DestroyField(user: $request->user(), field: $target)->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', [
            'template' => $template,
            'page' => $templateVersionSection->templateVersionPage->key,
        ])
            ->with('status', __('The field is removed.'))
            ->with('status_description', __('Nothing had been written in it.'));
    }

    /**
     * When the section had to be copied, the field the URL names belongs to
     * the version left behind, and its twin in the copy answers to the same
     * key.
     */
    private function editable(Request $request, TemplateVersionSection $row, Field $field): Field
    {
        if ($field->section_version_id !== $row->section_version_id) {
            throw new ModelNotFoundException('This field is not in this section.');
        }

        $sectionVersion = new MakeSectionVersionEditable(
            user: $request->user(),
            row: $row,
        )->execute();

        if ($sectionVersion->id === $field->section_version_id) {
            return $field;
        }

        return $sectionVersion->fields()->where('key', $field->key)->firstOrFail();
    }

    private function valueList(Request $request, FieldTypeEnum $type): ?ValueList
    {
        if (! $type->usesValueList()) {
            return null;
        }

        return ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->find($request->integer('value_list_id'));
    }

    private function refuse(Template $template): RedirectResponse
    {
        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('error', __('This version has been published and cannot be changed.'))
            ->with('error_description', __('Start a draft, and the published version goes on serving the records already on it.'));
    }
}
