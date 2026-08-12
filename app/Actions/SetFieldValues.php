<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Exceptions\FieldIsRequired;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Save one section of a record: everything the template asks inside it, in one
 * go.
 *
 * It exists because a section is the unit somebody actually fills in. The screen
 * reads until somebody opens one block of it, and what they then have in front
 * of them is that block's questions and nothing else, so that is what a save may
 * write and what it may refuse.
 *
 * The refusal is therefore narrower than it looks. A required field in another
 * section is not this save's business: it was never on the screen, nobody
 * declined to answer it, and holding this block hostage to it would be refusing
 * an edit because of a question somebody has not been asked. Nothing here can
 * say a record is complete, and nothing here pretends to.
 *
 * A field left empty is not skipped, it is cleared. Somebody who deletes what
 * they typed and saves has said something, and a save that quietly kept the old
 * answer would be losing their edit rather than keeping their data.
 *
 * It writes nothing itself. Each value goes through {@see SetFieldValue} and
 * each clearing through {@see DestroyFieldValue}, which is also where the
 * logging happens: this delegates the whole of its journal to the writes it
 * performs, rather than adding a line that says a section was saved and
 * repeating underneath it what each field now holds.
 */
class SetFieldValues
{
    private int $written = 0;

    /**
     * @param  array<int, mixed>  $values  what was typed, keyed by field id
     * @param  array<int, bool>  $yearIsUnknown
     * @param  array<int, bool>  $monthIsUnknown
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
        private readonly TemplateVersionSection $section,
        private readonly array $values,
        private readonly array $yearIsUnknown = [],
        private readonly array $monthIsUnknown = [],
    ) {}

    /**
     * How many fields the save touched, written and cleared together.
     */
    public function execute(): int
    {
        $this->validate();

        $fields = $this->fieldsInTheSection();

        $this->refuseAnEmptyRequiredField($fields);

        DB::transaction(function () use ($fields): void {
            foreach ($fields as $field) {
                $this->save($field);
            }
        });

        return $this->written;
    }

    private function validate(): void
    {
        if ($this->record->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }

        if ($this->section->template_version_id !== $this->record->template_version_id) {
            throw new InvalidArgumentException('This section belongs to another structure than the one the record renders from.');
        }
    }

    /**
     * The fields the section asks about, which are the ones sitting directly in
     * it. A field inside a repeater is filled in on its repetition rather than
     * here, and a type with no control on the page is not something this save
     * can have been given.
     *
     * @return Collection<int, Field>
     */
    private function fieldsInTheSection(): Collection
    {
        return $this->section->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->values();
    }

    /**
     * @param  Collection<int, Field>  $fields
     */
    private function refuseAnEmptyRequiredField(Collection $fields): void
    {
        $missing = $fields->first(
            fn (Field $field): bool => $field->is_required && $this->isBlank($this->given($field)),
        );

        if ($missing instanceof Field) {
            throw new FieldIsRequired("{$missing->label} has to be filled in.");
        }
    }

    private function save(Field $field): void
    {
        $given = $this->given($field);

        if ($this->isBlank($given)) {
            $this->clear($field);

            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $this->record,
            field: $field,
            value: $this->scalarFor($field, $given),
            valueListItem: $this->chosenItem($field, $given),
            valueListItems: $this->chosenItems($field, $given),
            yearIsUnknown: (bool) ($this->yearIsUnknown[$field->id] ?? false),
            monthIsUnknown: (bool) ($this->monthIsUnknown[$field->id] ?? false),
        )->execute();

        $this->written++;
    }

    /**
     * Take away what a field held, when there is anything to take away. A field
     * that was already empty is left alone rather than cleared again, so a save
     * that changed nothing writes nothing and says nothing in the journal.
     */
    private function clear(Field $field): void
    {
        $existing = FieldValue::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('field_id', $field->id)
            ->whereNull('field_value_row_id')
            ->first();

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

    /**
     * Whether nothing was given. A ticked box holding false is an answer and
     * not a blank, which is why the boolean type is asked about separately: "no"
     * is something somebody said.
     */
    private function isBlank(mixed $given): bool
    {
        if (is_bool($given)) {
            return false;
        }

        if (is_array($given)) {
            return $given === [];
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

    private function chosenItem(Field $field, mixed $given): ?ValueListItem
    {
        if ($field->type !== FieldTypeEnum::Select && $field->type !== FieldTypeEnum::Radio) {
            return null;
        }

        return ValueListItem::query()->find($given);
    }

    /**
     * @return list<ValueListItem>
     */
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
