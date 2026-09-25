<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Templates;

use App\Actions\CreateField;
use App\Actions\DestroyField;
use App\Actions\MakeSectionVersionEditable;
use App\Actions\UpdateField;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\CannotNestARepeater;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Helpers\FieldSettingsCatalogue;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class FieldController extends Controller
{
    public function new(Request $request, Template $template, StructureVersionSection $structureVersionSection): View
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        return view('app.settings.account.field-type', [
            'row' => $structureVersionSection,
            'sectionName' => $structureVersionSection->title_override ?? $structureVersionSection->sectionVersion->section->name,
            'backHref' => route('settings.templateBuilder.index', ['template' => $template, 'page' => $structureVersionSection->structureVersionPage->key]),
            'backLabel' => __('Back to the template'),
            'current' => 'templates',
            'action' => route('settings.fields.create', [$template, $structureVersionSection]),
            'catalogue' => new FieldCatalogueViewModel,
            'valueLists' => ValueList::query()
                ->where('account_id', $request->user()->account_id)
                ->where('is_shared', true)
                ->orderBy('position')
                ->get(),
            'entityTypes' => EntityType::query()
                ->where('account_id', $request->user()->account_id)
                ->where('is_active', true)
                ->orderBy('position')
                ->get(),
            'user' => $request->user(),
        ]);
    }

    public function create(Request $request, Template $template, StructureVersionSection $structureVersionSection): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(FieldTypeEnum::class)],
            'label' => ['required', 'string', 'max:255'],
            'value_list_id' => [
                'nullable',
                Rule::exists('value_lists', 'id')->where('account_id', $request->user()->account_id),
            ],
            'relation_target' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $type = FieldTypeEnum::from($validated['type']);
        $parent = isset($validated['parent_id']) ? $this->repeaterIn($structureVersionSection, (int) $validated['parent_id']) : null;
        $again = $parent === null
            ? route('settings.fields.new', [$template, $structureVersionSection])
            : route('settings.childFields.new', [$template, $structureVersionSection, $parent]);

        [$relationTarget, $relationEntityType] = $type->isRelation()
            ? $this->pointsAt($request, (string) ($validated['relation_target'] ?? ''))
            : [null, null];

        if ($type->isRelation() && $relationTarget === null) {
            return redirect($again)->withInput()->withErrors(['relation_target' => __('Choose what this field points at.')]);
        }

        try {
            $sectionVersion = new MakeSectionVersionEditable(
                user: $request->user(),
                row: $structureVersionSection,
            )->execute();

            $field = new CreateField(
                user: $request->user(),
                sectionVersion: $sectionVersion,
                type: $type,
                label: $validated['label'],
                parent: $parent === null ? null : $sectionVersion->fields()->where('key', $parent->key)->firstOrFail(),
                valueList: $this->valueList($request, $type),
                relationTarget: $relationTarget,
                relationEntityType: $relationEntityType,
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        } catch (CannotNestARepeater) {
            return redirect($again)->withInput()->withErrors(['type' => __('A repeater cannot sit inside another repeater.')]);
        } catch (InvalidArgumentException) {
            return redirect($again)
                ->with('error', __('A field that offers choices has to say which list they come from.'))
                ->with('error_description', __('Make the list on the value lists screen first, then come back.'));
        }

        return redirect()->route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $structureVersionSection->structureVersionPage->key,
            'field' => $field->id,
        ])->with('status', __('The field is added.'));
    }

    public function update(Request $request, Template $template, StructureVersionSection $structureVersionSection, Field $field): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['nullable', 'boolean'],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_highlighted' => ['nullable', 'boolean'],
            'repetition_order' => ['nullable', 'string', Rule::in(RepetitionOrderEnum::values())],
            'accepted_mime_types' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $target = $this->editable($request, $structureVersionSection, $field);

            $options = $target->options ?? [];

            if ($target->type->holdsAFile()) {
                unset($options['accepted_mime_types']);

                $kinds = FieldSettingsCatalogue::commaSeparated(mb_strtolower((string) ($validated['accepted_mime_types'] ?? '')));

                if ($kinds !== []) {
                    $options['accepted_mime_types'] = $kinds;
                }
            }

            new UpdateField(
                user: $request->user(),
                field: $target,
                label: $validated['label'],
                helpText: $validated['help_text'] ?? null,
                placeholder: $validated['placeholder'] ?? null,
                isRequired: $request->boolean('is_required'),
                isSensitive: $request->boolean('is_sensitive'),
                isHighlighted: $request->boolean('is_highlighted'),
                isUnique: $target->is_unique,
                isIdentifying: $target->is_identifying,
                valueList: $target->valueList,
                defaultValue: $target->default_value,
                options: $options === [] ? null : $options,
                validation: $target->validation,
                repeatMin: $target->repeat_min,
                repeatMax: $target->repeat_max,
                repetitionOrder: RepetitionOrderEnum::tryFrom((string) ($validated['repetition_order'] ?? '')),
            )->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        } catch (AcceptedFileKindIsNotValid $e) {
            return back()->withInput()->withErrors(['accepted_mime_types' => $e->getMessage()]);
        }

        return redirect()->route('settings.templateBuilder.field', [
            'template' => $template,
            'page' => $structureVersionSection->structureVersionPage->key,
            'field' => $target->getKey(),
        ])->with('status', __('The field is saved.'));
    }

    public function destroy(Request $request, Template $template, StructureVersionSection $structureVersionSection, Field $field): RedirectResponse
    {
        abort_unless($structureVersionSection->isReachableThrough($request->user(), $template), 404);

        try {
            $target = $this->editable($request, $structureVersionSection, $field);

            new DestroyField(user: $request->user(), field: $target)->execute();
        } catch (PublishedVersionIsFrozen) {
            return $this->refuse($template);
        }

        return redirect()->route('settings.templateBuilder.index', [
            'template' => $template,
            'page' => $structureVersionSection->structureVersionPage->key,
        ])
            ->with('status', __('The field is removed.'))
            ->with('status_description', __('Nothing had been written in it.'));
    }

    private function editable(Request $request, StructureVersionSection $row, Field $field): Field
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

    /**
     * The repeater a new field goes inside, as the section placed here holds it.
     * The action copies a shared or published section before writing to it, so
     * the caller finds the same repeater again in the copy by its key.
     */
    private function repeaterIn(StructureVersionSection $row, int $id): Field
    {
        $repeater = Field::query()
            ->where('section_version_id', $row->section_version_id)
            ->findOrFail($id);

        abort_unless($repeater->type->isRepeater(), 404);

        return $repeater;
    }

    private function valueList(Request $request, FieldTypeEnum $type): ?ValueList
    {
        if (! $type->usesValueList()) {
            return null;
        }

        return ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_shared', true)
            ->find($request->integer('value_list_id'));
    }

    /**
     * What a relation about to be added points at: somebody in the vault, or a
     * kind of record of this account that is still turned on. Nothing when the
     * screen sent neither.
     *
     * @return array{0: RelationTargetEnum|null, 1: EntityType|null}
     */
    private function pointsAt(Request $request, string $value): array
    {
        [$target, $entityTypeId] = FieldSettingsCatalogue::target($value) ?? [null, null];

        if ($target !== RelationTargetEnum::Entity) {
            return [$target, null];
        }

        $entityType = EntityType::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_active', true)
            ->find($entityTypeId);

        return $entityType === null ? [null, null] : [$target, $entityType];
    }

    private function refuse(Template $template): RedirectResponse
    {
        return redirect()->route('settings.templateBuilder.index', $template)
            ->with('error', __('This version has been published and cannot be changed.'))
            ->with('error_description', __('Start a draft, and the published version keeps serving the records already on it.'));
    }
}
