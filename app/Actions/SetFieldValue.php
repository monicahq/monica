<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationTargetEnum;
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
use App\Models\User;
use App\Models\ValueListItem;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Write down what somebody typed into one field of one record.
 *
 * It is the only place a value is written, and everything that makes a value
 * interpretable later is settled here. The column is chosen by the type of the
 * field rather than by the caller, since exactly one of them is populated and
 * which one is a property of the type. The key of the field is copied onto the
 * row for the same reason an email address is copied onto a log: the value has
 * to remain readable after the field it came from has left the structure.
 *
 * Writing to a field whose values had been set aside brings this one back. It
 * is the same gesture as re-adding the field, made one value at a time.
 *
 * What it refuses is what would otherwise be stored as a quiet lie: a layout
 * field that stores nothing, a repeater given a value rather than a repetition,
 * a choice that is not on the list the field offers, a relation pointing at the
 * wrong kind of record, or a second record holding a value the field says only
 * one may hold.
 */
class SetFieldValue
{
    private FieldValue $fieldValue;

    private ?FieldValue $existing = null;

    private string $column;

    private mixed $columnValue = null;

    /**
     * @param  list<ValueListItem>  $valueListItems
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $valuable,
        private readonly Field $field,
        private readonly ?FieldValueRow $row = null,
        private readonly mixed $value = null,
        private readonly ?ValueListItem $valueListItem = null,
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

    /**
     * Every value inside a repeater carries the repetition it belongs to, and
     * every value outside one carries none. Neither half is optional: a value
     * with no repetition would be a phone number belonging to no line, and a
     * value with one that is not the repetition of its own repeater would
     * belong to somebody else's.
     */
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

    /**
     * A relation points at exactly one record, of exactly the kind the field
     * says it points at, living in the same vault as the record holding it. The
     * vault is a compartment, and a value crossing one would be a way of
     * reading what is on the other side of it.
     */
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

    /**
     * Find what this field already holds on this record, in this repetition.
     * One field on one record in one repetition holds one value, so writing
     * again replaces rather than adds.
     */
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
            'value_json' => $this->value,
            'value_list_item_id' => $this->valueListItem?->id,
            'value_list_item_ids' => array_map(fn (ValueListItem $item): int => $item->id, $this->valueListItems),
            'value_file_id' => $this->file?->id,
            default => $this->value,
        };
    }

    /**
     * Read the date, with the year replaced by a placeholder when nobody knows
     * it. Rewriting it here rather than trusting the caller is what makes the
     * promise that the year is never rendered true everywhere: nothing
     * downstream can read a year out of the column and believe it.
     */
    private function readTheDate(): mixed
    {
        if ($this->value === null || ! $this->missesPart($this->yearIsUnknown)) {
            return $this->value;
        }

        $date = new DateTimeImmutable((string) $this->value);

        return $date->setDate(FieldValue::PLACEHOLDER_YEAR, (int) $date->format('n'), (int) $date->format('j'))
            ->format('Y-m-d');
    }

    /**
     * Whether a part of the date is genuinely missing. Only a date that comes
     * round again may have one, so the flag is dropped rather than stored on
     * anything else: a setting a type cannot honour is a setting that lies.
     *
     * The two parts are independent. A birthday whose year nobody knows and a
     * date somebody remembers only the year of are both normal cases.
     */
    private function missesPart(bool $flag): bool
    {
        return $flag && $this->field->type->mayHaveAnUnknownPart();
    }

    /**
     * Refuse a value another record in the same vault already holds, when the
     * field says only one of them may. A field holding several values at once
     * is skipped: uniqueness across sets is a different question, and not one
     * the specification asks.
     */
    private function refuseADuplicate(): void
    {
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
                array_merge($this->emptyColumns(), [
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

    /**
     * Every column the value is not written into, so that replacing a value
     * never leaves the leftovers of what was there before beside it.
     *
     * @return array<string, null>
     */
    private function emptyColumns(): array
    {
        $columns = [
            'value_string', 'value_text', 'value_number', 'value_boolean', 'value_date',
            'value_datetime', 'value_json', 'value_list_item_id', 'value_list_item_ids',
            'value_file_id', 'related_contact_id', 'related_entity_id',
        ];

        return array_fill_keys(array_diff($columns, [$this->column]), null);
    }

    /**
     * Hand the reference over from the file that was there to the one that is
     * now. Detaching never deletes, so a file nothing points at any more waits
     * out the grace period before the sweep takes it.
     */
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

    /**
     * Keep the count of what points at each item of a value list in step. It is
     * what somebody about to disable an item is shown, and counting on read
     * would mean aggregating the largest table in the product.
     */
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
