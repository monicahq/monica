<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What each column of the list of people holds for each person on one page. Every field value the
 * columns need is read in one query, however many columns there are.
 */
class ContactListCells
{
    private const string BIRTHDAY_KEY = 'birthday';

    /** @var array<int, array<int, list<string>>>|null */
    private ?array $values = null;

    /**
     * @param  list<ContactListColumn>  $columns
     * @param  list<Contact>  $contacts
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly array $columns,
        private readonly array $contacts,
    ) {}

    /**
     * The values of every column for one person, in the order of the columns. A column with
     * nothing to show holds an empty list.
     *
     * @return list<list<string>>
     */
    public function for(Contact $contact): array
    {
        $cells = [];

        foreach ($this->columns as $index => $column) {
            $cells[] = match ($column->source) {
                ContactListColumnSourceEnum::Nickname => $contact->nickname === null || $contact->nickname === '' ? [] : [$contact->nickname],
                ContactListColumnSourceEnum::Template => [$contact->structureVersion->structure->owner->name],
                ContactListColumnSourceEnum::LastSpoke => $contact->last_interaction_at === null ? [] : [$contact->last_interaction_at->diffForHumans()],
                ContactListColumnSourceEnum::Added => [$contact->created_at->isoFormat('ll')],
                default => $this->values()[$contact->id][$index] ?? [],
            };
        }

        return $cells;
    }

    /** @return array<int, array<int, list<string>>> */
    private function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $this->values = [];
        $fields = $this->fields();

        if ($fields->isEmpty() || $this->contacts === []) {
            return $this->values;
        }

        $formatter = new FieldValueFormatter($this->user);

        $fieldValues = FieldValue::query()
            ->where('vault_id', $this->vault->id)
            ->where('valuable_type', new Contact()->getMorphClass())
            ->whereIn('valuable_id', array_map(fn (Contact $contact): int => $contact->id, $this->contacts))
            ->whereIn('field_id', $fields->modelKeys())
            ->where('is_detached', false)
            ->with(['row', 'valueListItem'])
            ->get()
            ->each(fn (FieldValue $value) => $value->setRelation('field', $fields->find($value->field_id)))
            ->sortBy([
                fn (FieldValue $a, FieldValue $b): int => $a->field->position <=> $b->field->position,
                fn (FieldValue $a, FieldValue $b): int => ($a->row->position ?? 0) <=> ($b->row->position ?? 0),
                fn (FieldValue $a, FieldValue $b): int => $a->id <=> $b->id,
            ]);

        foreach ($fieldValues as $value) {
            $written = $formatter->format($value);

            if ($written === null) {
                continue;
            }

            foreach ($this->columns as $index => $column) {
                if ($this->feeds($value->field, $column)) {
                    $this->values[$value->valuable_id][$index][] = $written;
                }
            }
        }

        return $this->values;
    }

    private function feeds(Field $field, ContactListColumn $column): bool
    {
        return match ($column->source) {
            ContactListColumnSourceEnum::Email => $field->type === FieldTypeEnum::Email,
            ContactListColumnSourceEnum::Phone => $field->type === FieldTypeEnum::Phone,
            ContactListColumnSourceEnum::Birthday => $field->key === self::BIRTHDAY_KEY,
            ContactListColumnSourceEnum::Field => $field->key === $column->field_key
                && $field->sectionVersion->section_id === $column->section_id,
            default => false,
        };
    }

    /**
     * Every field of the account, in any version of any section, that feeds one of the columns.
     *
     * @return Collection<int, Field>
     */
    private function fields(): Collection
    {
        $sources = array_map(fn (ContactListColumn $column): ContactListColumnSourceEnum => $column->source, $this->columns);
        $fieldColumns = array_filter($this->columns, fn (ContactListColumn $column): bool => $column->source === ContactListColumnSourceEnum::Field);

        $types = array_values(array_filter([
            in_array(ContactListColumnSourceEnum::Email, $sources, true) ? FieldTypeEnum::Email : null,
            in_array(ContactListColumnSourceEnum::Phone, $sources, true) ? FieldTypeEnum::Phone : null,
        ]));
        $wantsBirthday = in_array(ContactListColumnSourceEnum::Birthday, $sources, true);

        if ($types === [] && ! $wantsBirthday && $fieldColumns === []) {
            return new Collection;
        }

        return Field::query()
            ->where('account_id', $this->vault->account_id)
            ->where('is_sensitive', false)
            ->where(function (Builder $query) use ($types, $wantsBirthday, $fieldColumns): void {
                if ($types !== []) {
                    $query->orWhereIn('type', $types);
                }

                if ($wantsBirthday) {
                    $query->orWhere('key', self::BIRTHDAY_KEY);
                }

                foreach ($fieldColumns as $column) {
                    $query->orWhere(function (Builder $query) use ($column): void {
                        $query->where('key', $column->field_key)
                            ->whereHas('sectionVersion', fn (Builder $query) => $query->where('section_id', $column->section_id));
                    });
                }
            })
            ->with('sectionVersion')
            ->get();
    }
}
