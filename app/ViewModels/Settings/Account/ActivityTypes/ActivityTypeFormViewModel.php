<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\ActivityTypes;

use App\Enums\FieldTypeEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\FieldSettingsCatalogue;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueListItem;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Support\Facades\Gate;

class ActivityTypeFormViewModel
{
    /**
     * @var array<int, array{reference: string, placementId: ?int, name: string, isGroup: bool, fields: array<int, array<string, mixed>>}>|null
     */
    private ?array $blocks = null;

    /** @var array<int, string>|null */
    private ?array $entityTypes = null;

    public function __construct(
        private readonly User $user,
        private readonly ?ActivityType $activityType = null,
        private readonly ?ActivityCategory $wantedCategory = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function isEdit(): bool
    {
        return $this->activityType !== null;
    }

    public function isSystem(): bool
    {
        return $this->activityType->is_system ?? false;
    }

    public function title(): string
    {
        return $this->activityType->name ?? __('Add an activity type');
    }

    public function description(): string
    {
        if ($this->activityType === null) {
            return __('Name what happened, file it under a category, and give it a mark so the journal reads at a glance.');
        }

        if ($this->activityType->is_system) {
            return __('A type that came with your account. Its wording, its category and its mark are yours to change. It cannot be deleted, only switched off.');
        }

        return __('Your own type. Everything here is yours to change.');
    }

    public function action(): string
    {
        if ($this->activityType === null) {
            return route('settings.activityTypes.create');
        }

        return route('settings.activityTypes.update', $this->activityType);
    }

    public function method(): string
    {
        return $this->activityType === null ? 'post' : 'put';
    }

    public function submitLabel(): string
    {
        return $this->activityType === null ? __('Add the type') : __('Save changes');
    }

    public function backUrl(): string
    {
        $category = $this->activityType->activityCategory ?? $this->wantedCategory;

        if ($category === null) {
            return route('settings.activityTypes.index');
        }

        return route('settings.activityTypes.category', $category);
    }

    public function name(): string
    {
        return (string) old('name', $this->activityType?->getRawOriginal('name') ?? $this->activityType->name ?? '');
    }

    public function descriptionValue(): string
    {
        return (string) old('description', $this->activityType->description ?? '');
    }

    public function icon(): ?string
    {
        $value = old('icon', $this->activityType?->icon);

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function colour(): ?string
    {
        $value = old('colour', $this->activityType?->colour);

        return $value === null || $value === '' ? null : (string) $value;
    }

    public function categoryId(): ?int
    {
        $value = old('activity_category_id') ?? $this->activityType->activity_category_id ?? $this->wantedCategory?->id;

        return $value === null ? $this->categories()[0]['id'] ?? null : (int) $value;
    }

    /**
     * Every category the type may be filed under. One that is switched off is
     * offered and says so, because a type already filed there has to stay
     * choosable rather than silently move.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function categories(): array
    {
        return ActivityCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (ActivityCategory $category): array => [
                'id' => $category->id,
                'label' => $category->is_active
                    ? $category->name
                    : __(':name (switched off)', ['name' => $category->name]),
            ])
            ->all();
    }

    /** @return array<int, string> */
    public function glyphs(): array
    {
        return Section::ICONS;
    }

    /**
     * @return array<int, array{value: string|null, label: string, tint: string, swatch: string|null}>
     */
    public function colours(): array
    {
        $neutral = [[
            'value' => null,
            'label' => __('Neutral'),
            'tint' => VaultColourEnum::neutralTint(),
            'swatch' => null,
        ]];

        return array_merge($neutral, array_map(
            fn (VaultColourEnum $colour): array => [
                'value' => $colour->value,
                'label' => __($colour->label()),
                'tint' => $colour->tint(),
                'swatch' => $colour->value,
            ],
            VaultColourEnum::cases(),
        ));
    }

    public function isActive(): bool
    {
        if (old('is_active') !== null) {
            return (bool) old('is_active');
        }

        return $this->activityType->is_active ?? true;
    }

    public function availabilityHelp(): string
    {
        if ($this->isActive()) {
            return __('Switch this off to retire the type. It leaves the picker, and the activities recorded with it keep their type, their mark and their fields.');
        }

        return __('This type is not offered for new activities. The ones already recorded with it are unchanged.');
    }

    /**
     * The kinds of field somebody may pick, under the families the catalogue
     * files them in. Only the kinds the application can already write are
     * offered, so the list never promises something that cannot be saved.
     *
     * @return array<int, array{name: string, types: array<int, array{key: string, label: string}>}>
     */
    public function fieldTypeFamilies(): array
    {
        $families = [];

        foreach (new FieldCatalogueViewModel()->families() as $family) {
            $available = array_values(array_map(
                fn (array $type): array => ['key' => $type['key'], 'label' => $type['label']],
                array_filter($family['types'], fn (array $type): bool => $type['isAvailable']),
            ));

            if ($available !== []) {
                $families[] = ['name' => $family['name'], 'types' => $available];
            }
        }

        return $families;
    }

    /**
     * The mark drawn beside each kind of field, so a row can show what it is
     * without reading its own label.
     *
     * @return array<string, string>
     */
    public function fieldTypeMarks(): array
    {
        $marks = [];

        foreach (new FieldCatalogueViewModel()->types() as $type) {
            if ($type['isAvailable']) {
                $marks[$type['key']] = $type['mark'];
            }
        }

        return $marks;
    }

    /**
     * What each kind of field asks for beyond its name. Every kind is described
     * here rather than only the ones on screen, because somebody changing a
     * row's kind has to be shown the new settings without a round trip.
     *
     * @return array<string, list<array{key: string, label: string, control: string, width: string, placeholder: ?string, options: array<int, array{value: string, label: string}>}>>
     */
    public function settingsByFieldType(): array
    {
        $settings = [];

        foreach (FieldTypeEnum::cases() as $type) {
            $settings[$type->value] = FieldSettingsCatalogue::of($type, $this->entityTypeNames());
        }

        return $settings;
    }

    /** @return array<int, string> */
    public function typesOfferingChoices(): array
    {
        return array_values(array_map(
            fn (FieldTypeEnum $type): string => $type->value,
            array_filter(FieldTypeEnum::cases(), FieldSettingsCatalogue::offersChoices(...)),
        ));
    }

    /**
     * The groups, then the block holding the fields that are in no group. The
     * loose block is always last and always there, because it is where a field
     * lands when nobody has made a group for it.
     *
     * @return array<int, array{reference: string, placementId: ?int, name: string, isGroup: bool, fields: array<int, array<string, mixed>>}>
     */
    public function blocks(): array
    {
        $this->blocks ??= $this->cameBack() ? $this->blocksAsSubmitted() : $this->blocksAsSaved();

        return $this->blocks;
    }

    public function canDelete(): bool
    {
        if ($this->activityType === null) {
            return false;
        }

        return Gate::forUser($this->user)->allows('delete', $this->activityType);
    }

    public function deleteRefusal(): ?string
    {
        if ($this->activityType === null) {
            return __('Once it is saved, the type can be deleted for as long as no activity uses it.');
        }

        $response = Gate::forUser($this->user)->inspect('delete', $this->activityType);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function deleteAction(): ?string
    {
        return $this->activityType === null
            ? null
            : route('settings.activityTypes.destroy', $this->activityType);
    }

    /**
     * @return array<int, array{reference: string, placementId: ?int, name: string, isGroup: bool, fields: array<int, array<string, mixed>>}>
     */
    private function blocksAsSaved(): array
    {
        $version = $this->publishedVersion();

        if ($version === null) {
            return [$this->looseBlock([])];
        }

        $groups = [];
        $loose = [];

        foreach ($version->assembly as $row) {
            $fields = $this->fieldsOf($row, $row->shows_title ? 'g'.$row->id : '');

            if (! $row->shows_title) {
                $loose = $fields;

                continue;
            }

            $groups[] = [
                'reference' => 'g'.$row->id,
                'placementId' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'isGroup' => true,
                'fields' => $fields,
            ];
        }

        return array_merge($groups, [$this->looseBlock($loose)]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldsOf(StructureVersionSection $row, string $reference): array
    {
        return $row->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->sortBy('position')
            ->map(fn (Field $field): array => [
                'reference' => 'f'.$field->id,
                'key' => $field->key,
                'group' => $reference,
                'label' => $field->label,
                'type' => $field->type->value,
                'isRequired' => $field->is_required,
                'helpText' => (string) $field->help_text,
                'settings' => $this->settingsOf($field),
                'choices' => $field->valueList?->items
                    ->sortBy('position')
                    ->map(fn (ValueListItem $item): string => $item->label)
                    ->values()
                    ->all() ?? [],
            ])
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    private function settingsOf(Field $field): array
    {
        $settings = [];

        foreach (FieldSettingsCatalogue::of($field->type, $this->entityTypeNames()) as $setting) {
            $settings[$setting['key']] = FieldSettingsCatalogue::read(
                key: $setting['key'],
                options: $field->options,
                column: $this->columnFor($field, $setting['key']),
            );
        }

        return $settings;
    }

    private function columnFor(Field $field, string $key): mixed
    {
        return match ($key) {
            'placeholder' => $field->placeholder,
            'repeat_min' => $field->repeat_min,
            'repeat_max' => $field->repeat_max,
            'repetition_order' => $field->repetition_order?->value,
            'relation_target' => $field->relation_entity_type_id === null
                ? $field->relation_target?->value
                : $field->relation_target?->value.':'.$field->relation_entity_type_id,
            default => null,
        };
    }

    /**
     * Read the editor back out of what was just submitted, so a form that came
     * back with an error comes back with every group and field the reader had
     * typed rather than what was last saved.
     *
     * @return array<int, array{reference: string, placementId: ?int, name: string, isGroup: bool, fields: array<int, array<string, mixed>>}>
     */
    private function blocksAsSubmitted(): array
    {
        $fields = [];

        foreach ((array) old('fields', []) as $reference => $field) {
            $fields[] = [
                'reference' => (string) $reference,
                'key' => $field['key'] ?? null,
                'group' => (string) ($field['group'] ?? ''),
                'label' => (string) ($field['label'] ?? ''),
                'type' => (string) ($field['type'] ?? FieldTypeEnum::Text->value),
                'isRequired' => (bool) ($field['is_required'] ?? false),
                'helpText' => (string) ($field['help_text'] ?? ''),
                'settings' => array_map(strval(...), (array) ($field['settings'] ?? [])),
                'choices' => array_values(array_map(strval(...), (array) ($field['choices'] ?? []))),
            ];
        }

        $blocks = [];

        foreach ((array) old('groups', []) as $reference => $group) {
            $blocks[] = [
                'reference' => (string) $reference,
                'placementId' => ($group['placement_id'] ?? '') === '' ? null : (int) $group['placement_id'],
                'name' => (string) ($group['name'] ?? ''),
                'isGroup' => true,
                'fields' => array_values(array_filter($fields, fn (array $f): bool => $f['group'] === (string) $reference)),
            ];
        }

        $blocks[] = $this->looseBlock(array_values(array_filter($fields, fn (array $f): bool => $f['group'] === '')));

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array{reference: string, placementId: ?int, name: string, isGroup: bool, fields: array<int, array<string, mixed>>}
     */
    private function looseBlock(array $fields): array
    {
        return [
            'reference' => '',
            'placementId' => null,
            'name' => __('Not in a group'),
            'isGroup' => false,
            'fields' => $fields,
        ];
    }

    /**
     * Whether this is the form coming back with an error rather than opening.
     * Availability is the one answer the form always sends, checked or not, so
     * it is the one that says which of the two happened. Nothing else can: a
     * form sent with every group and every field taken away sends neither.
     */
    private function cameBack(): bool
    {
        return old('is_active') !== null;
    }

    private function publishedVersion(): ?StructureVersion
    {
        return $this->activityType?->structure?->versions()->first();
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
