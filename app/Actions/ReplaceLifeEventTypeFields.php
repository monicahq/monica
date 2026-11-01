<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Helpers\FieldSettingsCatalogue;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\LifeEventType;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Write what a life event of one nature asks for, as one whole.
 *
 * The screen edits every group and every field at once and saves them together,
 * so this takes the finished layout rather than one change at a time. It opens
 * a draft of what the type publishes today, makes the draft match what was sent,
 * and publishes it. A life event already recorded keeps pointing at the version
 * it was written on and never follows.
 *
 * A group is a section whose name is drawn above its fields. The fields nobody
 * grouped are a section too, placed first with its name hidden, because a field
 * belongs to a section version and to nothing else.
 *
 * A field is matched to the one already there by its key. Changing what kind of
 * field it is, or what a relation points at, is not something a field can be
 * updated into: those are destroyed and written again in place, under the same
 * key.
 */
class ReplaceLifeEventTypeFields
{
    private StructureVersion $version;

    private StructureVersionPage $page;

    /**
     * The placement each submitted group ended up in, by the reference the
     * screen gave it. The fields that are in no group are filed under the empty
     * reference.
     *
     * @var array<string, StructureVersionSection>
     */
    private array $placements = [];

    /**
     * Which section version each placement carried before a draft was opened.
     *
     * Drafting copies the placements, so the numbers the screen was given no
     * longer name a row. What survives the copy is the section version each one
     * points at, and that is what matches a group the reader kept to the row it
     * now sits in.
     *
     * @var array<int, int>
     */
    private array $sectionVersionOfPlacement = [];

    /**
     * What a relation may point at, read once rather than for every field.
     *
     * @var array<int, string>|null
     */
    private ?array $entityTypes = null;

    /**
     * @param  list<array{reference: string, placement_id: ?int, name: string}>  $groups
     * @param  list<array{key: ?string, group: string, label: string, type: string, is_required: bool, help_text: ?string, settings: array<string, string>, choices: list<string>}>  $fields
     */
    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
        private readonly array $groups,
        private readonly array $fields,
    ) {}

    public function execute(): StructureVersion
    {
        $this->validate();

        return DB::transaction(function (): StructureVersion {
            $this->openADraft();
            $this->settleTheGroups();
            $this->settleTheFields();

            return new PublishStructureVersion(
                user: $this->user,
                structureVersion: $this->version,
                changeNote: $this->version->previous_version_id === null
                    ? null
                    : __('The fields of :name were saved.', ['name' => $this->lifeEventType->name]),
            )->execute();
        });
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }

        if ($this->lifeEventType->structure === null) {
            throw new ModelNotFoundException('This life event type carries no structure.');
        }
    }

    private function openADraft(): void
    {
        $current = $this->lifeEventType->structure->versions()->firstOrFail();

        $this->sectionVersionOfPlacement = StructureVersionSection::query()
            ->where('structure_version_id', $current->id)
            ->pluck('section_version_id', 'id')
            ->all();

        $this->version = $current->status->isEditable()
            ? $current
            : new CreateStructureVersionDraft(user: $this->user, structureVersion: $current)->execute();

        $this->page = $this->version->pages()->firstOrFail();
    }

    private function settleTheGroups(): void
    {
        $standing = StructureVersionSection::query()
            ->where('structure_version_id', $this->version->id)
            ->get()
            ->keyBy('id');

        $byPlacement = $standing;
        $bySectionVersion = $standing->keyBy('section_version_id');
        $loose = $standing->firstWhere('shows_title', false);
        $kept = [];

        if ($this->needsALooseBlock()) {
            $this->placements[''] = $loose ?? $this->placeASection(name: __('Fields in no block'), showsTitle: false);
            $kept[] = $this->placements['']->id;
        }

        foreach ($this->groups as $group) {
            $standingRow = $this->rowKeptFor($group['placement_id'], $byPlacement, $bySectionVersion);

            $row = $standingRow !== null && $standingRow->shows_title
                ? $this->rename($standingRow, $group['name'])
                : $this->placeASection(name: $group['name'], showsTitle: true);

            $this->placements[$group['reference']] = $row;
            $kept[] = $row->id;
        }

        $standing->reject(fn (StructureVersionSection $row): bool => in_array($row->id, $kept, true))
            ->each(function (StructureVersionSection $row): void {
                SectionVersion::query()->whereKey($row->section_version_id)->where('usage_count', '>', 0)->decrement('usage_count');
                $row->delete();
            });

        $this->renumberTheGroups();
    }

    /**
     * The row a group the reader kept now sits in, whether or not opening a
     * draft has renumbered it.
     *
     * @param  Collection<int, StructureVersionSection>  $byPlacement
     * @param  Collection<int, StructureVersionSection>  $bySectionVersion
     */
    private function rowKeptFor(?int $placementId, $byPlacement, $bySectionVersion): ?StructureVersionSection
    {
        if ($placementId === null) {
            return null;
        }

        if ($byPlacement->has($placementId)) {
            return $byPlacement->get($placementId);
        }

        $sectionVersionId = $this->sectionVersionOfPlacement[$placementId] ?? null;

        return $sectionVersionId === null ? null : $bySectionVersion->get($sectionVersionId);
    }

    private function needsALooseBlock(): bool
    {
        foreach ($this->fields as $field) {
            if ($field['group'] === '') {
                return true;
            }
        }

        return false;
    }

    private function placeASection(string $name, bool $showsTitle): StructureVersionSection
    {
        $section = new CreateSection(user: $this->user, name: $name)->execute();

        return new AddSectionToStructureVersion(
            user: $this->user,
            structureVersion: $this->version,
            sectionVersion: $section->versions()->firstOrFail(),
            titleOverride: $showsTitle ? $name : null,
            showsTitle: $showsTitle,
            structureVersionPage: $this->page,
        )->execute();
    }

    private function rename(StructureVersionSection $row, string $name): StructureVersionSection
    {
        $row->title_override = $name;
        $row->save();

        return $row;
    }

    /**
     * The fields in no group are read first, before any heading, which is what
     * puts their placement at the front of the page.
     */
    private function renumberTheGroups(): void
    {
        $order = array_merge(
            isset($this->placements['']) ? [$this->placements['']] : [],
            array_map(fn (array $group): StructureVersionSection => $this->placements[$group['reference']], $this->groups),
        );

        foreach ($order as $index => $row) {
            $row->position = $index + 1;
            $row->save();
        }
    }

    private function settleTheFields(): void
    {
        foreach ($this->placements as $reference => $row) {
            $sectionVersion = new MakeSectionVersionEditable(user: $this->user, row: $row)->execute();

            $wanted = array_values(array_filter(
                $this->fields,
                fn (array $field): bool => $field['group'] === $reference,
            ));

            $this->settleOneSection($sectionVersion, $wanted);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $wanted
     */
    private function settleOneSection(SectionVersion $sectionVersion, array $wanted): void
    {
        $standing = $sectionVersion->fields()
            ->whereNull('parent_field_id')
            ->get()
            ->keyBy('key');

        $kept = [];

        foreach ($wanted as $index => $field) {
            $written = $this->writeOneField($sectionVersion, $standing->get((string) $field['key']), $field);

            $written->position = $index + 1;
            $written->save();

            $kept[] = $written->key;
        }

        $standing->reject(fn (Field $field): bool => in_array($field->key, $kept, true))
            ->each(fn (Field $field) => new DestroyField(user: $this->user, field: $field)->execute());
    }

    /**
     * @param  array<string, mixed>  $wanted
     */
    private function writeOneField(SectionVersion $sectionVersion, ?Field $standing, array $wanted): Field
    {
        $type = FieldTypeEnum::from($wanted['type']);
        $settings = $this->settings($type, $wanted['settings']);
        $valueList = $this->valueListFor($type, $standing, $wanted);

        if ($standing !== null && $this->keepsItsIdentity($standing, $type, $settings)) {
            return new UpdateField(
                user: $this->user,
                field: $standing,
                label: $wanted['label'],
                helpText: $wanted['help_text'],
                placeholder: $settings['placeholder'] ?? null,
                isRequired: $wanted['is_required'],
                isSensitive: $standing->is_sensitive,
                isHighlighted: $standing->is_highlighted,
                isUnique: $standing->is_unique,
                isIdentifying: $standing->is_identifying,
                valueList: $valueList,
                options: $settings['options'],
                repeatMin: $settings['repeat_min'] ?? null,
                repeatMax: $settings['repeat_max'] ?? null,
                repetitionOrder: $settings['repetition_order'] ?? null,
            )->execute();
        }

        $key = $standing?->key;

        if ($standing !== null) {
            new DestroyField(user: $this->user, field: $standing)->execute();
        }

        return new CreateField(
            user: $this->user,
            sectionVersion: $sectionVersion,
            type: $type,
            label: $wanted['label'],
            key: $key,
            helpText: $wanted['help_text'],
            placeholder: $settings['placeholder'] ?? null,
            isRequired: $wanted['is_required'],
            valueList: $valueList,
            options: $settings['options'],
            repeatMin: $settings['repeat_min'] ?? null,
            repeatMax: $settings['repeat_max'] ?? null,
            repetitionOrder: $settings['repetition_order'] ?? null,
            relationTarget: $settings['relation_target'] ?? null,
            relationEntityType: $settings['relation_entity_type'] ?? null,
        )->execute();
    }

    /**
     * Whether the field already there is still the same field. Its kind, and
     * what a relation points at, are settled when it is written and there is no
     * way to update either, so a change to one means writing it again.
     *
     * @param  array<string, mixed>  $settings
     */
    private function keepsItsIdentity(Field $standing, FieldTypeEnum $type, array $settings): bool
    {
        if ($standing->type !== $type) {
            return false;
        }

        if (! $type->isRelation()) {
            return true;
        }

        return $standing->relation_target === ($settings['relation_target'] ?? RelationTargetEnum::Contact)
            && $standing->relation_entity_type_id === ($settings['relation_entity_type'] ?? null)?->id;
    }

    /**
     * Read the settings the screen sent into the shape each one is stored in:
     * a column of the field for some, a key of its options for the rest.
     *
     * @param  array<string, string>  $sent
     * @return array<string, mixed>
     */
    private function settings(FieldTypeEnum $type, array $sent): array
    {
        $settled = ['options' => null];
        $options = [];

        foreach (FieldSettingsCatalogue::of($type, $this->entityTypeNames()) as $setting) {
            $value = trim($sent[$setting['key']] ?? '');

            if ($value === '') {
                continue;
            }

            match ($setting['key']) {
                'placeholder' => $settled['placeholder'] = $value,
                'repeat_min' => $settled['repeat_min'] = (int) $value,
                'repeat_max' => $settled['repeat_max'] = (int) $value,
                'repetition_order' => $settled['repetition_order'] = RepetitionOrderEnum::tryFrom($value),
                'relation_target' => $this->pointAt($settled, $value),
                'accepted_mime_types' => $options['accepted_mime_types'] = FieldSettingsCatalogue::commaSeparated($value),
                'max_length', 'min', 'max', 'decimals', 'max_choices' => $options[$setting['key']] = (int) $value,
                default => $options[$setting['key']] = $value,
            };
        }

        $settled['options'] = $options === [] ? null : $options;

        return $settled;
    }

    /**
     * @param  array<string, mixed>  $settled
     */
    private function pointAt(array &$settled, string $value): void
    {
        [$target, $entityTypeId] = FieldSettingsCatalogue::target($value) ?? [RelationTargetEnum::Entity, null];

        $settled['relation_target'] = $target;

        if ($target === RelationTargetEnum::Contact) {
            return;
        }

        $settled['relation_entity_type'] = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->find($entityTypeId);
    }

    /**
     * The list a choice field reads from. It belongs to that field alone: it is
     * written here so a set of choices is held one way rather than two, and it
     * is kept off the value lists screen because nobody named it.
     *
     * @param  array<string, mixed>  $wanted
     */
    private function valueListFor(FieldTypeEnum $type, ?Field $standing, array $wanted): ?ValueList
    {
        if (! $type->usesValueList()) {
            return null;
        }

        $list = $standing?->valueList;

        if ($list === null || $list->is_shared) {
            $list = new CreateValueList(
                user: $this->user,
                name: $wanted['label'],
                isShared: false,
            )->execute();
        } else {
            new UpdateValueList(user: $this->user, valueList: $list, name: $wanted['label'])->execute();
        }

        $this->settleTheChoices($list, $wanted['choices']);

        return $list->refresh();
    }

    /**
     * @param  list<string>  $choices
     */
    private function settleTheChoices(ValueList $list, array $choices): void
    {
        $standing = $list->items()->orderBy('position')->get()->values();

        foreach ($choices as $index => $label) {
            $item = $standing->get($index);

            if ($item === null) {
                new CreateValueListItem(user: $this->user, valueList: $list, label: $label)->execute();

                continue;
            }

            new UpdateValueListItem(user: $this->user, item: $item, label: $label)->execute();
        }

        $standing->slice(count($choices))
            ->each(fn ($item) => new DestroyValueListItem(user: $this->user, item: $item)->execute());
    }

    /** @return array<int, string> */
    private function entityTypeNames(): array
    {
        return $this->entityTypes ??= EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->pluck('name_singular', 'id')
            ->all();
    }
}
