<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ValueListEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\ReminderNeedsADate;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\Reminder;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Save one section of a record: everything the template asks inside it, in one go.
 */
class SetFieldValues
{
    private int $written = 0;

    /** @var array<int, string|null> */
    private array $datesBefore = [];

    /**
     * @param  array<int, mixed>  $values  what was typed, keyed by field id
     * @param  array<int, mixed>  $labels  the label picked beside a value, keyed by field id
     * @param  array<int, mixed>  $labelWordings  the wording typed when that label is the open one
     * @param  array<int, bool>  $yearIsUnknown
     * @param  array<int, bool>  $monthIsUnknown
     * @param  array<int, array<string, mixed>>  $reminders  what was asked of each date, keyed by field id
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
        private readonly StructureVersionSection $section,
        private readonly array $values,
        private readonly array $labels = [],
        private readonly array $labelWordings = [],
        private readonly array $yearIsUnknown = [],
        private readonly array $monthIsUnknown = [],
        private readonly array $reminders = [],
    ) {}

    public function execute(): int
    {
        $this->validate();

        $fields = $this->fieldsInTheSection();

        $this->refuseAnEmptyRequiredField($fields);
        $this->refuseAReminderWithNoDate($fields);

        DB::transaction(function () use ($fields): void {
            $this->rememberTheDates($fields);

            foreach ($fields as $field) {
                $this->save($field);
            }

            foreach ($fields as $field) {
                $this->saveTheReminder($field);
            }
        });

        return $this->written;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->section->structure_version_id !== $this->record->structure_version_id) {
            throw new InvalidArgumentException('This section belongs to another structure than the one the record renders from.');
        }
    }

    /** @return Collection<int, Field> */
    private function fieldsInTheSection(): Collection
    {
        return $this->section->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->values();
    }

    /** @param  Collection<int, Field>  $fields */
    private function refuseAnEmptyRequiredField(Collection $fields): void
    {
        $missing = $fields->first(
            fn (Field $field): bool => $field->is_required && $this->nothingWasGivenFor($field),
        );

        if ($missing instanceof Field) {
            throw new FieldIsRequired("{$missing->label} has to be filled in.");
        }
    }

    /** @param  Collection<int, Field>  $fields */
    private function refuseAReminderWithNoDate(Collection $fields): void
    {
        $asked = $fields->first(
            fn (Field $field): bool => $field->type->canDriveAReminder()
                && $this->wantsAReminderOn($field)
                && $this->isBlank($this->given($field)),
        );

        if ($asked instanceof Field) {
            throw new ReminderNeedsADate("There is no date in {$asked->label} to be reminded of.");
        }
    }

    /** @param  Collection<int, Field>  $fields */
    private function rememberTheDates(Collection $fields): void
    {
        foreach ($fields as $field) {
            if ($field->type->canDriveAReminder()) {
                $this->datesBefore[$field->id] = $this->dateSignature($this->valueOn($field));
            }
        }
    }

    private function dateSignature(?FieldValue $value): ?string
    {
        if ($value?->value_date === null) {
            return null;
        }

        return implode('|', [
            $value->value_date->format('Y-m-d'),
            $value->year_is_unknown ? 'y' : '',
            $value->month_is_unknown ? 'm' : '',
        ]);
    }

    private function rebuildIfTheDateMoved(Field $field, Reminder $rule, FieldValue $value): void
    {
        if (($this->datesBefore[$field->id] ?? null) === $this->dateSignature($value)) {
            return;
        }

        new CancelReminderOccurrences(
            reminder: $rule,
            reason: ReminderOccurrenceCancelledReasonEnum::SourceChanged,
        )->execute();

        $rule->schedule_revision++;
        $rule->save();

        new GenerateReminderOccurrences(reminder: $rule->refresh())->execute();
    }

    private function saveTheReminder(Field $field): void
    {
        if (! $field->type->canDriveAReminder()) {
            return;
        }

        $rule = $this->ruleOn($field);
        $value = $this->valueOn($field);

        if ($value?->value_date === null) {
            if ($rule instanceof Reminder && $rule->is_active) {
                new DeactivateReminders(
                    user: $this->user,
                    subject: $rule,
                    reason: ReminderInactiveReasonEnum::ValueCleared,
                )->execute();
            }

            return;
        }

        if (! $this->wantsAReminderOn($field)) {
            if ($rule instanceof Reminder) {
                new DestroyReminder(user: $this->user, reminder: $rule)->execute();
            }

            return;
        }

        if ($rule instanceof Reminder && $rule->is_active && $rule->field_value_id === $value->id) {
            $this->changeTheRule($field, $rule);
            $this->rebuildIfTheDateMoved($field, $rule->refresh(), $value);

            return;
        }

        if ($rule instanceof Reminder) {
            new DestroyReminder(user: $this->user, reminder: $rule)->execute();
        }

        $this->makeTheRule($field, $value);
    }

    private function makeTheRule(Field $field, FieldValue $value): void
    {
        new CreateReminder(
            user: $this->user,
            fieldValue: $value,
            cadence: $this->cadenceFor($field),
            moments: $this->momentsFor($field),
            timeOfDay: $this->timeFor($field),
            name: $this->asked($field, 'name'),
            frequencyInterval: (int) ($this->asked($field, 'interval') ?? 1),
            timezone: $this->asked($field, 'timezone'),
        )->execute();
    }

    private function changeTheRule(Field $field, Reminder $rule): void
    {
        new UpdateReminder(
            user: $this->user,
            reminder: $rule,
            cadence: $this->cadenceFor($field),
            moments: $this->momentsFor($field),
            timeOfDay: $this->timeFor($field),
            name: $this->asked($field, 'name'),
            frequencyInterval: (int) ($this->asked($field, 'interval') ?? 1),
            timezone: $this->asked($field, 'timezone'),
        )->execute();
    }

    private function ruleOn(Field $field): ?Reminder
    {
        return Reminder::query()
            ->where('account_id', $this->user->account_id)
            ->where('remindable_type', $this->record->getMorphClass())
            ->where('remindable_id', $this->record->id)
            ->where('field_id', $field->id)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();
    }

    private function valueOn(Field $field): ?FieldValue
    {
        return FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('field_id', $field->id)
            ->whereNull('field_value_row_id')
            ->first();
    }

    private function wantsAReminderOn(Field $field): bool
    {
        return filter_var($this->asked($field, 'on') ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function cadenceFor(Field $field): ReminderCadenceEnum
    {
        if (! filter_var($this->asked($field, 'recurs') ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ReminderCadenceEnum::Once;
        }

        return ReminderCadenceEnum::tryFrom((string) $this->asked($field, 'cadence')) ?? ReminderCadenceEnum::Yearly;
    }

    /** @return array<int, array<array-key, mixed>> */
    private function momentsFor(Field $field): array
    {
        $moments = $this->reminders[$field->id]['moments'] ?? [];

        return is_array($moments) ? array_values(array_filter($moments, 'is_array')) : [];
    }

    private function timeFor(Field $field): string
    {
        return (string) ($this->asked($field, 'time') ?? Reminder::DEFAULT_TIME);
    }

    private function asked(Field $field, string $key): ?string
    {
        $value = $this->reminders[$field->id][$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function save(Field $field): void
    {
        if ($field->type->isRepeater()) {
            $this->saveTheRepetitions($field);

            return;
        }

        $this->saveOne(
            $field,
            $this->given($field),
            null,
            $this->labels[$field->id] ?? null,
            $this->labelWordings[$field->id] ?? null,
        );
    }

    private function saveOne(Field $field, mixed $given, ?FieldValueRow $row, mixed $label = null, mixed $wording = null): void
    {
        if ($field->type->holdsAFile()) {
            $this->saveTheFile($field, $given, $row);

            return;
        }

        if ($this->isBlank($given)) {
            $this->clear($field, $row);

            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $this->record,
            field: $field,
            row: $row,
            value: $field->type === FieldTypeEnum::Address
                ? $this->addressFor($field, is_array($given) ? $given : [], $row)
                : $this->scalarFor($field, $given),
            valueListItem: $this->chosenItem($field, $given) ?? $this->chosenLabel($field, $label),
            labelWording: $wording === null ? null : (string) $wording,
            valueListItems: $this->chosenItems($field, $given),
            yearIsUnknown: (bool) ($this->yearIsUnknown[$field->id] ?? false),
            monthIsUnknown: (bool) ($this->monthIsUnknown[$field->id] ?? false),
        )->execute();

        $this->written++;
    }

    private function saveTheFile(Field $field, mixed $given, ?FieldValueRow $row): void
    {
        if (! $given instanceof UploadedFile) {
            return;
        }

        $file = new CreateFile(
            user: $this->user,
            upload: $given,
            vault: $this->record->vault,
        )->execute();

        new SetFieldValue(
            user: $this->user,
            valuable: $this->record,
            field: $field,
            row: $row,
            file: $file,
        )->execute();

        $this->written++;
    }

    private function saveTheRepetitions(Field $field): void
    {
        $given = $this->given($field);
        $submitted = is_array($given) ? $given : [];
        $existing = $this->repetitionsOf($field);
        $children = $this->childrenOf($field);
        $kept = [];
        $ordered = [];

        ksort($submitted, SORT_NUMERIC);

        foreach ($submitted as $repetition) {
            if (! is_array($repetition)) {
                continue;
            }

            $values = is_array($repetition['values'] ?? null) ? $repetition['values'] : [];
            $labels = is_array($repetition['labels'] ?? null) ? $repetition['labels'] : [];
            $wordings = is_array($repetition['label_wordings'] ?? null) ? $repetition['label_wordings'] : [];
            $row = $existing->get((int) ($repetition['id'] ?? 0));

            if ($row === null && $this->nothingWasFilledIn($children, $values)) {
                continue;
            }

            $row ??= new CreateFieldValueRow(
                user: $this->user,
                valuable: $this->record,
                field: $field,
            )->execute();

            $kept[] = $row->id;
            $ordered[] = $row;

            foreach ($children as $child) {
                $this->saveOne($child, $values[$child->id] ?? null, $row, $labels[$child->id] ?? null, $wordings[$child->id] ?? null);
            }
        }

        foreach ($existing as $row) {
            if (in_array($row->id, $kept, true)) {
                continue;
            }

            new DestroyFieldValueRow(user: $this->user, row: $row)->execute();

            $this->written++;
        }

        $this->numberTheRepetitions($ordered);
    }

    /** @param  list<FieldValueRow>  $ordered */
    private function numberTheRepetitions(array $ordered): void
    {
        foreach (array_values($ordered) as $index => $row) {
            $position = $index + 1;

            if ($row->position === $position) {
                continue;
            }

            $row->position = $position;
            $row->save();
        }
    }

    /** @return Collection<int, FieldValueRow> */
    private function repetitionsOf(Field $field): Collection
    {
        return FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('field_id', $field->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<int, Field> */
    private function childrenOf(Field $field): Collection
    {
        return $field->children
            ->filter(fn (Field $child): bool => $child->type->hasAnInput() && ! $child->type->isRepeater())
            ->values();
    }

    /**
     * @param  Collection<int, Field>  $children
     * @param  array<int, mixed>  $values
     */
    private function nothingWasFilledIn(Collection $children, array $values): bool
    {
        return $children->every(fn (Field $child): bool => $this->isBlank($values[$child->id] ?? null));
    }

    private function clear(Field $field, ?FieldValueRow $row): void
    {
        $query = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('field_id', $field->id);

        $row === null
            ? $query->whereNull('field_value_row_id')
            : $query->where('field_value_row_id', $row->id);

        $existing = $query->first();

        if ($existing === null) {
            return;
        }

        new DestroyFieldValue(user: $this->user, fieldValue: $existing)->execute();

        $this->written++;
    }

    private function given(Field $field): mixed
    {
        return $this->values[$field->id] ?? null;
    }

    private function nothingWasGivenFor(Field $field): bool
    {
        $given = $this->given($field);

        if (! $field->type->isRepeater()) {
            return $this->isBlank($given);
        }

        if (! is_array($given) || $given === []) {
            return true;
        }

        $children = $this->childrenOf($field);

        foreach ($given as $repetition) {
            if (! is_array($repetition)) {
                continue;
            }

            if (($repetition['id'] ?? null) !== null && $repetition['id'] !== '') {
                return false;
            }

            if (! $this->nothingWasFilledIn($children, is_array($repetition['values'] ?? null) ? $repetition['values'] : [])) {
                return false;
            }
        }

        return true;
    }

    private function isBlank(mixed $given): bool
    {
        if (is_bool($given) || $given instanceof UploadedFile) {
            return false;
        }

        if (is_array($given)) {
            return array_all($given, fn (mixed $part): bool => $this->isBlank($part));
        }

        return $given === null || mb_trim((string) $given) === '';
    }

    private function scalarFor(Field $field, mixed $given): mixed
    {
        if ($field->type->usesValueList()) {
            return null;
        }

        return $field->type === FieldTypeEnum::Boolean ? filter_var($given, FILTER_VALIDATE_BOOLEAN) : $given;
    }

    /**
     * @param  array<string, mixed>  $given
     * @return array<string, mixed>
     */
    private function addressFor(Field $field, array $given, ?FieldValueRow $row): array
    {
        $country = $given['country'] ?? '';

        if ($country === 'keep') {
            $stored = FieldValue::query()
                ->where('valuable_type', $this->record->getMorphClass())
                ->where('valuable_id', $this->record->id)
                ->where('field_id', $field->id)
                ->where('field_value_row_id', $row?->id)
                ->first();

            return array_merge($given, ['country' => (string) ($stored->value_json['country'] ?? '')]);
        }

        if ($country === '') {
            return array_merge($given, ['country' => '']);
        }

        if (filter_var($country, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('A country is picked from the list of countries rather than typed.');
        }

        $item = ValueListItem::query()
            ->whereKey($country)
            ->where('account_id', $this->user->account_id)
            ->first();

        if ($item === null) {
            throw new ModelNotFoundException('This country is in another account.');
        }

        $countries = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->where('key', ValueListEnum::Countries->value)
            ->first();

        if ($item->value_list_id !== $countries?->id) {
            throw new InvalidArgumentException("The item {$item->label} is not on the list of countries.");
        }

        return array_merge($given, ['country' => $item->label]);
    }

    private function chosenLabel(Field $field, mixed $label): ?ValueListItem
    {
        if (! $field->type->usesALabelList() || $label === null || $label === '') {
            return null;
        }

        return ValueListItem::query()->find($label);
    }

    private function chosenItem(Field $field, mixed $given): ?ValueListItem
    {
        if ($field->type !== FieldTypeEnum::Select && $field->type !== FieldTypeEnum::Radio) {
            return null;
        }

        return ValueListItem::query()->find($given);
    }

    /** @return list<ValueListItem> */
    private function chosenItems(Field $field, mixed $given): array
    {
        if ($field->type !== FieldTypeEnum::Multiselect || ! is_array($given)) {
            return [];
        }

        return ValueListItem::query()
            ->whereIn('id', $given)
            ->orderBy('position')
            ->get()
            ->all();
    }
}
