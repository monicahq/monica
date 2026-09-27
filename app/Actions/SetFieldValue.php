<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\FieldValueIsNotUnique;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\LifeEvent;
use App\Models\Reminder;
use App\Models\User;
use App\Models\ValueListItem;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write down what somebody typed into one field of one record.
 */
class SetFieldValue
{
    private FieldValue $fieldValue;

    private ?FieldValue $existing = null;

    private string $column;

    private mixed $columnValue = null;

    /** @param  list<ValueListItem>  $valueListItems */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity|LifeEvent $valuable,
        private readonly Field $field,
        private readonly ?FieldValueRow $row = null,
        private readonly mixed $value = null,
        private readonly ?ValueListItem $valueListItem = null,
        private readonly ?string $labelWording = null,
        private readonly array $valueListItems = [],
        private readonly ?File $file = null,
        private readonly ?Contact $relatedContact = null,
        private readonly ?Entity $relatedEntity = null,
        private readonly bool $yearIsUnknown = false,
        private readonly bool $monthIsUnknown = false,
    ) {}

    public function execute(): FieldValue
    {
        $this->validate();
        $this->readTheExistingValue();
        $this->readTheColumn();
        $this->refuseADuplicate();
        $this->write();
        $this->moveTheReminder();
        $this->log();

        return $this->fieldValue;
    }

    private function validate(): void
    {
        if ($this->valuable->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }

        if (! $this->field->type->storesValue()) {
            throw new InvalidArgumentException('A layout field never produces a value.');
        }

        if ($this->field->type->isRepeater()) {
            throw new InvalidArgumentException('A repeater holds repetitions rather than a value of its own.');
        }

        $this->validateTheRow();
        $this->validateTheChoices();
        $this->validateTheRelation();
        $this->validateTheFile();
    }

    private function validateTheRow(): void
    {
        $sitsInARepeater = $this->field->parent_field_id !== null;

        if ($sitsInARepeater && $this->row === null) {
            throw new InvalidArgumentException('This field sits inside a repeater, so its value needs a repetition.');
        }

        if (! $sitsInARepeater && $this->row instanceof FieldValueRow) {
            throw new InvalidArgumentException('This field sits outside a repeater, so its value takes no repetition.');
        }

        if ($this->row === null) {
            return;
        }

        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This repetition is in another account.');
        }

        if ($this->row->valuable_type !== $this->valuable->getMorphClass() || $this->row->valuable_id !== $this->valuable->id) {
            throw new InvalidArgumentException('This repetition belongs to another record.');
        }

        if ($this->row->field_id !== $this->field->parent_field_id) {
            throw new InvalidArgumentException('This repetition belongs to another repeater.');
        }
    }

    private function validateTheChoices(): void
    {
        if ($this->field->type->usesALabelList()) {
            $this->validateTheLabel();

            return;
        }

        if (! $this->field->type->usesValueList()) {
            return;
        }

        $chosen = $this->valueListItem instanceof ValueListItem
            ? [$this->valueListItem]
            : $this->valueListItems;

        if ($chosen === []) {
            throw new InvalidArgumentException('This field picks from a list, so it needs an item of that list.');
        }

        foreach ($chosen as $item) {
            if ($item->value_list_id !== $this->field->value_list_id) {
                throw new InvalidArgumentException("The item {$item->label} is not on the list this field offers.");
            }
        }
    }

    private function validateTheRelation(): void
    {
        if (! $this->field->type->isRelation()) {
            return;
        }

        $target = $this->field->relation_target === RelationTargetEnum::Contact
            ? $this->relatedContact
            : $this->relatedEntity;

        if ($target === null) {
            throw new InvalidArgumentException('This relation points at nothing of the kind the field asks for.');
        }

        if ($target->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($target->vault_id !== $this->valuable->vault_id) {
            throw new InvalidArgumentException('A relation cannot cross a vault.');
        }

        if ($target instanceof Entity && $this->field->relation_entity_type_id !== null && $target->entity_type_id !== $this->field->relation_entity_type_id) {
            throw new InvalidArgumentException('This relation points at another kind of record.');
        }
    }

    private function validateTheFile(): void
    {
        if ($this->field->type->valueColumn() !== 'value_file_id') {
            return;
        }

        if ($this->file === null) {
            throw new InvalidArgumentException('This field holds a file, so it needs one.');
        }

        if ($this->file->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This file is in another account.');
        }
    }

    private function validateTheLabel(): void
    {
        if (! $this->valueListItem instanceof ValueListItem) {
            return;
        }

        if ($this->valueListItem->value_list_id !== $this->field->value_list_id) {
            throw new InvalidArgumentException("The item {$this->valueListItem->label} is not on the list this field offers.");
        }
    }

    /** @return array<string, int|string|null> */
    private function theLabel(): array
    {
        if (! $this->field->type->usesALabelList()) {
            return [];
        }

        $wording = $this->valueListItem?->takesATypedWording() === true
            ? TextSanitizer::nullablePlainText($this->labelWording)
            : null;

        return [
            'value_list_item_id' => $this->valueListItem?->id,
            'label_wording' => $wording === null || mb_trim($wording) === '' ? null : mb_trim($wording),
        ];
    }

    private function moveTheReminder(): void
    {
        if (! $this->field->type->canDriveAReminder()) {
            return;
        }

        $rule = $this->fieldValue->reminder;

        if (! $rule instanceof Reminder || ! $rule->is_active) {
            return;
        }

        if ($this->dateSignature($this->existing) === $this->dateSignature($this->fieldValue)) {
            return;
        }

        new CancelReminderOccurrences(
            reminder: $rule,
            reason: ReminderOccurrenceCancelledReasonEnum::SourceChanged,
            includePast: false,
        )->execute();

        $rule->schedule_revision++;
        $rule->save();

        new GenerateReminderOccurrences(reminder: $rule->refresh())->execute();
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

    private function readTheExistingValue(): void
    {
        $this->existing = FieldValue::query()
            ->where('valuable_type', $this->valuable->getMorphClass())
            ->where('valuable_id', $this->valuable->id)
            ->where('field_id', $this->field->id)
            ->where('field_value_row_id', $this->row?->id)
            ->first();
    }

    private function readTheColumn(): void
    {
        if ($this->field->type->isRelation()) {
            $this->column = $this->field->relation_target === RelationTargetEnum::Contact
                ? 'related_contact_id'
                : 'related_entity_id';
            $this->columnValue = $this->field->relation_target === RelationTargetEnum::Contact
                ? $this->relatedContact?->id
                : $this->relatedEntity?->id;

            return;
        }

        $this->column = (string) $this->field->type->valueColumn();

        $this->columnValue = match ($this->column) {
            'value_string', 'value_text' => TextSanitizer::nullablePlainText($this->value === null ? null : (string) $this->value),
            'value_number' => $this->value === null ? null : (float) $this->value,
            'value_boolean' => (bool) $this->value,
            'value_date' => $this->readTheDate(),
            'value_json' => $this->field->type === FieldTypeEnum::Address ? $this->address() : $this->value,
            'value_list_item_id' => $this->valueListItem?->id,
            'value_list_item_ids' => array_map(fn (ValueListItem $item): int => $item->id, $this->valueListItems),
            'value_file_id' => $this->file?->id,
            default => $this->value,
        };
    }

    /** @return array<string, string> */
    private function address(): array
    {
        $given = is_array($this->value) ? $this->value : [];
        $address = [];

        foreach (FieldValue::ADDRESS_PARTS as $part) {
            $address[$part] = is_scalar($given[$part] ?? null)
                ? TextSanitizer::plainText((string) $given[$part])
                : '';
        }

        return $address;
    }

    private function readTheDate(): mixed
    {
        if ($this->value === null || ! $this->missesPart($this->yearIsUnknown)) {
            return $this->value;
        }

        $date = new DateTimeImmutable((string) $this->value);

        return $date->setDate(FieldValue::PLACEHOLDER_YEAR, (int) $date->format('n'), (int) $date->format('j'))
            ->format('Y-m-d');
    }

    private function missesPart(bool $flag): bool
    {
        return $flag && $this->field->type->mayHaveAnUnknownPart();
    }

    private function refuseADuplicate(): void
    {
        $this->refuseADuplicateInTheList();

        if (! $this->field->is_unique || $this->columnValue === null || is_array($this->columnValue)) {
            return;
        }

        $taken = FieldValue::query()
            ->where('vault_id', $this->valuable->vault_id)
            ->where('field_id', $this->field->id)
            ->where($this->column, $this->columnValue)
            ->when($this->existing !== null, fn (Builder $query): Builder => $query->whereKeyNot($this->existing->id))
            ->exists();

        if ($taken) {
            throw new FieldValueIsNotUnique("Another record in this vault already holds this {$this->field->label}.");
        }
    }

    private function refuseADuplicateInTheList(): void
    {
        if (($this->field->options['distinct_in_list'] ?? false) !== true) {
            return;
        }

        if ($this->columnValue === null || is_array($this->columnValue)) {
            return;
        }

        $taken = FieldValue::query()
            ->where('valuable_type', $this->valuable->getMorphClass())
            ->where('valuable_id', $this->valuable->id)
            ->where('field_id', $this->field->id)
            ->where($this->column, $this->columnValue)
            ->when($this->existing !== null, fn (Builder $query): Builder => $query->whereKeyNot($this->existing->id))
            ->exists();

        if ($taken) {
            throw new FieldValueIsNotUnique("{$this->field->label} is already in this list.");
        }
    }

    private function write(): void
    {
        DB::transaction(function (): void {
            $this->moveTheFileCount();
            $this->moveTheChoiceCounts();

            $this->fieldValue = FieldValue::query()->updateOrCreate(
                [
                    'valuable_type' => $this->valuable->getMorphClass(),
                    'valuable_id' => $this->valuable->id,
                    'field_id' => $this->field->id,
                    'field_value_row_id' => $this->row?->id,
                ],
                array_merge($this->emptyColumns(), $this->theLabel(), [
                    'account_id' => $this->user->account_id,
                    'vault_id' => $this->valuable->vault_id,
                    'field_key' => $this->field->key,
                    $this->column => $this->columnValue,
                    'year_is_unknown' => $this->missesPart($this->yearIsUnknown),
                    'month_is_unknown' => $this->missesPart($this->monthIsUnknown),
                    'is_detached' => false,
                    'detached_at' => null,
                ]),
            );
        });
    }

    /** @return array<string, null> */
    private function emptyColumns(): array
    {
        $columns = [
            'value_string', 'value_text', 'value_number', 'value_boolean', 'value_date',
            'value_datetime', 'value_json', 'value_list_item_id', 'value_list_item_ids',
            'value_file_id', 'related_contact_id', 'related_entity_id', 'label_wording',
        ];

        return array_fill_keys(array_diff($columns, [$this->column], array_keys($this->theLabel())), null);
    }

    private function moveTheFileCount(): void
    {
        $before = $this->existing?->value_file_id;
        $after = $this->column === 'value_file_id' ? $this->columnValue : null;

        if ($before === $after) {
            return;
        }

        if ($before !== null && $this->existing?->file !== null) {
            new DetachFile(user: $this->user, file: $this->existing->file)->execute();
        }

        if ($this->file instanceof File && $after !== null) {
            new AttachFile(user: $this->user, file: $this->file)->execute();
        }
    }

    private function moveTheChoiceCounts(): void
    {
        $before = collect([$this->existing?->value_list_item_id])
            ->merge($this->existing->value_list_item_ids ?? [])
            ->filter()
            ->map(fn (int|string $id): int => (int) $id);

        $after = collect([$this->valueListItem?->id])
            ->merge(array_map(fn (ValueListItem $item): int => $item->id, $this->valueListItems))
            ->filter();

        ValueListItem::query()
            ->whereIn('id', $before->diff($after)->all())
            ->where('usage_count', '>', 0)
            ->decrement('usage_count');

        ValueListItem::query()
            ->whereIn('id', $after->diff($before)->all())
            ->increment('usage_count');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValueSet,
            vault: $this->valuable->vault,
            parameters: ['label' => $this->field->label, 'name' => $this->valuable->name],
            loggable: $this->fieldValue,
        )->onQueue('low');
    }
}
