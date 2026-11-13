<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PossessionTypeViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return $this->possessionType->name;
    }

    public function name(): string
    {
        return $this->possessionType->name;
    }

    public function colour(): string
    {
        return $this->possessionType->colour;
    }

    public function icon(): string
    {
        return $this->possessionType->icon ?? '';
    }

    /** @return list<array{value: string, isCurrent: bool}> */
    public function glyphs(): array
    {
        return array_map(
            fn (string $glyph): array => [
                'value' => $glyph,
                'isCurrent' => $glyph === $this->possessionType->icon,
            ],
            Section::ICONS,
        );
    }

    public function backUrl(): string
    {
        return route('settings.possessionTypes.index');
    }

    public function updateAction(): string
    {
        return route('settings.possessionTypes.update', $this->possessionType);
    }

    public function deleteAction(): string
    {
        return route('settings.possessionTypes.destroy', $this->possessionType);
    }

    public function exportUrl(): string
    {
        return route('settings.possessionTypeExport.index', $this->possessionType);
    }

    public function createGroupAction(): string
    {
        return route('settings.possessionFieldGroups.create', $this->possessionType);
    }

    public function createFieldAction(): string
    {
        return route('settings.possessionFields.create', $this->possessionType);
    }

    /**
     * The vault colours, each with its name, so a swatch is never told apart by its colour alone.
     *
     * @return list<array{value: string, label: string, isCurrent: bool}>
     */
    public function colours(): array
    {
        return array_map(
            fn (VaultColourEnum $colour): array => [
                'value' => $colour->value,
                'label' => __($colour->label()),
                'isCurrent' => $colour->value === $this->possessionType->colour,
            ],
            VaultColourEnum::cases(),
        );
    }

    /** @return list<array{value: string, label: string}> */
    public function kinds(): array
    {
        return array_map(
            fn (PossessionFieldKindEnum $kind): array => [
                'value' => $kind->value,
                'label' => __($kind->label()),
            ],
            PossessionFieldKindEnum::cases(),
        );
    }

    /**
     * The groups of the type in order, each with its fields in order.
     *
     * @return list<array{uuid: string, name: string, fieldCount: string, updateAction: string, destroyAction: string, positionAction: string, position: int, isFirst: bool, isLast: bool, createFieldAction: string, fields: list<array<string, mixed>>}>
     */
    public function groups(): array
    {
        $groups = $this->possessionType->groups()->with('fields')->get();

        return $groups
            ->map(fn (PossessionFieldGroup $group, int $index): array => [
                'uuid' => $group->uuid,
                'name' => $group->name,
                'fieldCount' => trans_choice(':count field|:count fields', $group->fields->count()),
                'updateAction' => route('settings.possessionFieldGroups.update', [$this->possessionType, $group]),
                'destroyAction' => route('settings.possessionFieldGroups.destroy', [$this->possessionType, $group]),
                'positionAction' => route('settings.possessionFieldGroupPosition.update', [$this->possessionType, $group]),
                'position' => $index + 1,
                'isFirst' => $index === 0,
                'isLast' => $index === $groups->count() - 1,
                'createFieldAction' => $this->createFieldAction(),
                'fields' => $this->fields($group->fields),
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function standaloneFields(): array
    {
        return $this->fields($this->possessionType->standaloneFields()->get());
    }

    public function holdsNothing(): bool
    {
        return $this->possessionType->fields()->doesntExist()
            && $this->possessionType->groups()->doesntExist();
    }

    public function summary(): string
    {
        $fieldCount = $this->possessionType->fields()->count();
        $groupCount = $this->possessionType->groups()->count();

        $fields = trans_choice(':count field|:count fields', $fieldCount);
        $groups = trans_choice(':count group|:count groups', $groupCount);

        return "{$fields} · {$groups}";
    }

    /**
     * @param  Collection<int, PossessionField>  $fields
     * @return list<array{uuid: string, name: string, kind: string, kindLabel: string, isSelect: bool, options: list<string>, choiceWarnings: array<string, string>, needsOptions: bool, updateAction: string, destroyAction: string, positionAction: string, position: int, isFirst: bool, isLast: bool}>
     */
    private function fields(Collection $fields): array
    {
        return $fields
            ->values()
            ->map(fn (PossessionField $field, int $index): array => [
                'uuid' => $field->uuid,
                'name' => $field->name,
                'kind' => $field->kind->value,
                'kindLabel' => __($field->kind->label()),
                'isSelect' => $field->kind === PossessionFieldKindEnum::Select,
                'options' => $field->options ?? [],
                'choiceWarnings' => $this->choiceWarnings($field),
                'needsOptions' => $field->kind === PossessionFieldKindEnum::Select && ($field->options ?? []) === [],
                'updateAction' => route('settings.possessionFields.update', [$this->possessionType, $field]),
                'destroyAction' => route('settings.possessionFields.destroy', [$this->possessionType, $field]),
                'positionAction' => route('settings.possessionFieldPosition.update', [$this->possessionType, $field]),
                'position' => $index + 1,
                'isFirst' => $index === 0,
                'isLast' => $index === $fields->count() - 1,
            ])
            ->all();
    }

    /** @return array<string, string> */
    private function choiceWarnings(PossessionField $field): array
    {
        if ($field->kind !== PossessionFieldKindEnum::Select) {
            return [];
        }

        $holders = $field->values()
            ->selectRaw('value, count(*) as holder_count')
            ->groupBy('value')
            ->pluck('holder_count', 'value');

        $warnings = [];

        foreach ($field->options ?? [] as $option) {
            $holderCount = (int) ($holders[$option] ?? 0);

            $warnings[$option] = $holderCount === 0
                ? __('This cannot be undone.')
                : trans_choice(':count possession holds this choice and will lose it. This cannot be undone.|:count possessions hold this choice and will lose it. This cannot be undone.', $holderCount);
        }

        return $warnings;
    }
}
