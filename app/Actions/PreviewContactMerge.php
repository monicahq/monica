<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\CannotAbsorbTheMeContact;
use App\Exceptions\CannotMergeAContactWithItself;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Work out what merging one contact into another would do, without doing any of
 * it.
 *
 * This is an action rather than a view model because both the screen and the API
 * ask it the same question, and a view model may only be read by Blade. Whatever
 * it answers here is what the merge is then allowed to write, so the two stay in
 * step: a resolution the preview never reported is refused.
 *
 * The fingerprint is what makes that promise hold over time. It covers both
 * contacts and every value they carry, so a merge confirmed after somebody
 * edited either of them underneath the preview is refused rather than applied to
 * a state nobody looked at. It hashes the values themselves rather than their
 * timestamps, because two edits inside one second share a timestamp and the
 * second of them would otherwise pass unnoticed.
 */
class PreviewContactMerge
{
    public function __construct(
        private readonly User $user,
        private readonly Contact $kept,
        private readonly Contact $absorbed,
    ) {}

    /**
     * @return array{
     *     kept: array{uuid: string, name: string},
     *     other: array{uuid: string, name: string},
     *     conflicts: list<array{field_key: string, field_id: int|null, label: string, row: int|null, kept_value: string, other_value: string}>,
     *     moves: list<array{kind: string, count: int}>,
     *     collisions: list<array{kind: string, field_key: string, row: int}>,
     *     set_aside: list<array{field_key: string, label: string, reason: string}>,
     *     fingerprint: string,
     * }
     */
    public function execute(): array
    {
        $this->validate();

        return [
            'kept' => ['uuid' => $this->kept->uuid, 'name' => $this->kept->name],
            'other' => ['uuid' => $this->absorbed->uuid, 'name' => $this->absorbed->name],
            'conflicts' => $this->conflicts(),
            'moves' => $this->moves(),
            'collisions' => $this->collisions(),
            'set_aside' => $this->setAside(),
            'fingerprint' => $this->fingerprint(),
        ];
    }

    private function validate(): void
    {
        if ($this->kept->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->absorbed->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->kept->vault_id !== $this->absorbed->vault_id) {
            throw new ModelNotFoundException('These two contacts are in different vaults.');
        }

        if ($this->kept->id === $this->absorbed->id) {
            throw new CannotMergeAContactWithItself('A contact cannot be merged with itself.');
        }

        if ($this->absorbed->is_me) {
            throw new CannotAbsorbTheMeContact('The contact representing you cannot be absorbed into another.');
        }
    }

    /** @return list<array{field_key: string, field_id: int|null, label: string, row: int|null, kept_value: string, other_value: string}> */
    private function conflicts(): array
    {
        $conflicts = [];

        if ($this->kept->avatar_file_id !== null && $this->absorbed->avatar_file_id !== null) {
            $conflicts[] = [
                'field_key' => 'avatar',
                'field_id' => null,
                'label' => __('Photo'),
                'row' => null,
                'kept_value' => __('the photo it has now'),
                'other_value' => __('the photo of :name', ['name' => $this->absorbed->name]),
            ];
        }

        $mine = $this->singleValues($this->kept);

        foreach ($this->singleValues($this->absorbed) as $fieldId => $theirs) {
            if (! isset($mine[$fieldId])) {
                continue;
            }

            if ($this->reads($mine[$fieldId]) === $this->reads($theirs)) {
                continue;
            }

            $conflicts[] = [
                'field_key' => $theirs->field_key,
                'field_id' => $fieldId,
                'label' => $theirs->field->label,
                'row' => null,
                'kept_value' => $this->reads($mine[$fieldId]),
                'other_value' => $this->reads($theirs),
            ];
        }

        return $conflicts;
    }

    /** @return list<array{kind: string, count: int}> */
    private function moves(): array
    {
        $carried = $this->fieldsTheSurvivorCarries();
        $mine = $this->singleValues($this->kept);

        $values = $this->attachedValues($this->absorbed)
            ->filter(fn (FieldValue $value): bool => in_array($value->field_id, $carried, true))
            ->reject(fn (FieldValue $value): bool => $value->field_value_row_id === null && isset($mine[$value->field_id]))
            ->count();

        $moves = [
            ['kind' => 'field_value', 'count' => $values],
            ['kind' => 'field_value_row', 'count' => $this->rows($this->absorbed)->count()],
            ['kind' => 'reminder', 'count' => $this->absorbed->reminders()->count()],
            ['kind' => 'share_link', 'count' => $this->absorbed->shareLinks()->count()],
            ['kind' => 'relation_pointer', 'count' => FieldValue::query()
                ->where('related_contact_id', $this->absorbed->id)
                ->count()],
        ];

        return array_values(array_filter($moves, fn (array $move): bool => $move['count'] > 0));
    }

    /**
     * Repeater rows are appended rather than compared, because two entries in a
     * repeater are two entries. An exact duplicate is still worth naming, so the
     * user can drop one instead of keeping the page saying the same thing twice.
     *
     * @return list<array{kind: string, field_key: string, row: int}>
     */
    private function collisions(): array
    {
        $mine = $this->rows($this->kept)
            ->mapWithKeys(fn (FieldValueRow $row): array => [$this->rowReads($row) => true])
            ->all();

        return $this->rows($this->absorbed)
            ->filter(fn (FieldValueRow $row): bool => isset($mine[$this->rowReads($row)]))
            ->map(fn (FieldValueRow $row): array => [
                'kind' => 'field_value_row',
                'field_key' => $row->field->key,
                'row' => $row->id,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{field_key: string, label: string, reason: string}> */
    private function setAside(): array
    {
        $carried = $this->fieldsTheSurvivorCarries();

        return $this->attachedValues($this->absorbed)
            ->reject(fn (FieldValue $value): bool => in_array($value->field_id, $carried, true))
            ->map(fn (FieldValue $value): array => [
                'field_key' => $value->field_key,
                'label' => $value->field->label,
                'reason' => __('no such field on the surviving structure'),
            ])
            ->unique('field_key')
            ->values()
            ->all();
    }

    private function fingerprint(): string
    {
        $parts = [];

        foreach ([$this->kept, $this->absorbed] as $contact) {
            $parts[] = "{$contact->id}:{$contact->sort_name}:{$contact->avatar_file_id}:{$contact->template_version_id}";

            foreach ($this->attachedValues($contact)->sortBy('id') as $value) {
                $parts[] = "{$value->id}={$this->reads($value)}";
            }
        }

        return hash('sha256', implode('|', $parts));
    }

    /** @return list<int> */
    private function fieldsTheSurvivorCarries(): array
    {
        return once(fn (): array => $this->kept->templateVersion->assembly
            ->flatMap(fn ($row): array => $row->sectionVersion->fields->pluck('id')->all())
            ->all());
    }

    /** @return Collection<int, FieldValue> */
    private function attachedValues(Contact $contact): Collection
    {
        return $contact->fieldValues()->attached()->with('field')->get();
    }

    /**
     * The values that sit directly in a section rather than inside a repeater
     * row, keyed by field, which is what makes two of them comparable.
     *
     * @return array<int, FieldValue>
     */
    private function singleValues(Contact $contact): array
    {
        return $this->attachedValues($contact)
            ->filter(fn (FieldValue $value): bool => $value->field_value_row_id === null)
            ->keyBy('field_id')
            ->all();
    }

    /** @return Collection<int, FieldValueRow> */
    private function rows(Contact $contact): Collection
    {
        return $contact->fieldValueRows()->with(['field', 'values'])->get();
    }

    private function reads(FieldValue $value): string
    {
        $read = $value->value;

        return is_array($read) ? json_encode($read) ?: '' : (string) $read;
    }

    private function rowReads(FieldValueRow $row): string
    {
        return $row->field_id.':'.$row->values
            ->sortBy('field_id')
            ->map(fn (FieldValue $value): string => $value->field_id.'='.$this->reads($value))
            ->implode(',');
    }
}
