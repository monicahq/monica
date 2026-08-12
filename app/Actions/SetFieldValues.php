<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FieldTypeEnum;
use App\Exceptions\FieldIsRequired;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
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
 * The same rule reaches repetitions. What arrives for a repeater is the whole of
 * it: a repetition carrying the number of a row is that row, one carrying no
 * number is a new one, and a row whose number did not arrive at all is one
 * somebody took away. Adding and removing repetitions is therefore part of
 * saving the block rather than a set of writes of its own, which is what lets
 * the block go on promising that nothing is written until it is saved.
 *
 * An upload is the one exception to all of it, and it has to be: a browser
 * cannot put a file back into an input it once left, so an empty file box means
 * the file is unchanged rather than gone. Replacing a document means choosing
 * another one, and removing it means removing the repetition it sits on.
 *
 * It writes nothing itself. Each value goes through {@see SetFieldValue}, each
 * clearing through {@see DestroyFieldValue}, each repetition through
 * {@see CreateFieldValueRow} and {@see DestroyFieldValueRow}, and each upload
 * through {@see CreateFile}, which is also where the logging happens: this
 * delegates the whole of its journal to the writes it performs, rather than
 * adding a line that says a section was saved and repeating underneath it what
 * each field now holds.
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
     * it. A field inside a repeater is not one of them: it is asked once per
     * repetition, and the repeater it belongs to is what carries it here.
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
            fn (Field $field): bool => $field->is_required && $this->nothingWasGivenFor($field),
        );

        if ($missing instanceof Field) {
            throw new FieldIsRequired("{$missing->label} has to be filled in.");
        }
    }

    private function save(Field $field): void
    {
        if ($field->type->isRepeater()) {
            $this->saveTheRepetitions($field);

            return;
        }

        $this->saveOne($field, $this->given($field), null);
    }

    /**
     * Write one answer, on the record itself or on one repetition of it.
     */
    private function saveOne(Field $field, mixed $given, ?FieldValueRow $row): void
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
            value: $this->scalarFor($field, $given),
            valueListItem: $this->chosenItem($field, $given),
            valueListItems: $this->chosenItems($field, $given),
            yearIsUnknown: (bool) ($this->yearIsUnknown[$field->id] ?? false),
            monthIsUnknown: (bool) ($this->monthIsUnknown[$field->id] ?? false),
        )->execute();

        $this->written++;
    }

    /**
     * Write an upload, when there is one.
     *
     * Nothing arriving is not an empty answer here. A file box comes back blank
     * every time the form is drawn, because no browser will hand a file back to
     * a page, so clearing on nothing would take somebody's document away the
     * moment they corrected the description beside it.
     */
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

    /**
     * Reconcile what the block says a repeater holds with what it held.
     *
     * A repetition with no number on it and nothing filled in is not a
     * repetition. Somebody who adds a row, thinks better of it and saves has
     * added nothing, and writing an empty row anyway would leave one behind
     * every time the block was opened.
     */
    private function saveTheRepetitions(Field $field): void
    {
        $given = $this->given($field);
        $submitted = is_array($given) ? $given : [];

        $existing = $this->repetitionsOf($field);
        $children = $this->childrenOf($field);
        $kept = [];

        foreach ($submitted as $repetition) {
            if (! is_array($repetition)) {
                continue;
            }

            $values = is_array($repetition['values'] ?? null) ? $repetition['values'] : [];
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

            foreach ($children as $child) {
                $this->saveOne($child, $values[$child->id] ?? null, $row);
            }
        }

        foreach ($existing as $row) {
            if (in_array($row->id, $kept, true)) {
                continue;
            }

            new DestroyFieldValueRow(user: $this->user, row: $row)->execute();

            $this->written++;
        }
    }

    /**
     * @return Collection<int, FieldValueRow>
     */
    private function repetitionsOf(Field $field): Collection
    {
        return FieldValueRow::query()
            ->where('valuable_type', $this->record->getMorphClass())
            ->where('valuable_id', $this->record->id)
            ->where('field_id', $field->id)
            ->orderBy('position')
            ->get()
            ->keyBy('id');
    }

    /**
     * What a repeater asks on each repetition, as far as this save can write it.
     * A repeater inside a repeater is refused where fields are created, and is
     * left out here too rather than trusted not to exist.
     *
     * @return Collection<int, Field>
     */
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

    /**
     * Take away what a field held, when there is anything to take away. A field
     * that was already empty is left alone rather than cleared again, so a save
     * that changed nothing writes nothing and says nothing in the journal.
     */
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

    /**
     * Whether the field was left unanswered, asked of the field rather than of
     * the value, because a repeater is answered by its repetitions: a list of
     * rows nobody filled in is as empty as no list at all.
     */
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

    /**
     * Whether nothing was given. A ticked box holding false is an answer and
     * not a blank, which is why the boolean type is asked about separately: "no"
     * is something somebody said. An upload is never blank either: it would not
     * be here at all if nobody had chosen it.
     */
    private function isBlank(mixed $given): bool
    {
        if (is_bool($given) || $given instanceof UploadedFile) {
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
