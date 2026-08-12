<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Give a record the answers its structure says it starts with.
 *
 * A field can carry a default, and until now it was handed to nobody: every
 * record began completely empty whatever its template said. Writing them here,
 * at creation, rather than pre-filling the form later, is what makes the
 * default true rather than merely suggested. Somebody who never opens the page
 * still has the answer the template promised, and the page shows the same thing
 * the API and an export do.
 *
 * A default that cannot be written is skipped rather than guessed at: the
 * shapes a `default_value` may take for a relation, a repeater or a file are
 * not settled, and writing something plausible into one of them would be
 * inventing the answer rather than reading it.
 */
class WriteDefaultValues
{
    private int $written = 0;

    public function __construct(
        private readonly User $user,
        private readonly Contact|Entity $record,
    ) {}

    /**
     * How many answers the record started with.
     */
    public function execute(): int
    {
        DB::transaction(function (): void {
            foreach ($this->fields() as $field) {
                $this->write($field);
            }
        });

        return $this->written;
    }

    /**
     * Every field the structure asks directly, on every page of it. A field
     * inside a repeater is left out: its default belongs to a repetition, and a
     * record starts with none.
     *
     * @return Collection<int, Field>
     */
    private function fields(): Collection
    {
        return $this->record->templateVersion->assembly
            ->flatMap(fn ($row): Collection => $row->sectionVersion->fields)
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->hasAnInput())
            ->filter(fn (Field $field): bool => $field->default_value !== null)
            ->values();
    }

    private function write(Field $field): void
    {
        $default = $field->default_value;

        /* The column is json, so a default written as a bare scalar arrives
           wrapped in nothing and one written as a list arrives as a list. What
           a choice field means by either is the id of an item. */
        $value = is_array($default) ? $default : [$default];

        if ($value === [] || $value[0] === null) {
            return;
        }

        $item = $this->chosenItem($field, $value[0]);
        $items = $this->chosenItems($field, $value);

        /* A default naming an item the list has stopped offering is dropped
           rather than written. The template says what a record starts with, and
           the list says what may be chosen; where the two disagree the record
           starts with nothing, which somebody can see and correct. */
        if ($field->type->usesValueList() && $item === null && $items === []) {
            return;
        }

        new SetFieldValue(
            user: $this->user,
            valuable: $this->record,
            field: $field,
            value: $field->type->usesValueList() ? null : $value[0],
            valueListItem: $item,
            valueListItems: $items,
        )->execute();

        $this->written++;
    }

    private function chosenItem(Field $field, mixed $given): ?ValueListItem
    {
        if ($field->type !== FieldTypeEnum::Select && $field->type !== FieldTypeEnum::Radio) {
            return null;
        }

        return ValueListItem::query()
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true)
            ->find($given);
    }

    /**
     * @param  array<int, mixed>  $given
     * @return list<ValueListItem>
     */
    private function chosenItems(Field $field, array $given): array
    {
        if ($field->type !== FieldTypeEnum::Multiselect) {
            return [];
        }

        return ValueListItem::query()
            ->whereIn('id', $given)
            ->where('value_list_id', $field->value_list_id)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->all();
    }
}
