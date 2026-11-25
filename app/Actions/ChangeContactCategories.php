<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ValueListEnum;
use App\Helpers\OwnedFields;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Give several contacts of one vault the categories chosen for each, in place of the ones they held.
 * Each value is written as editing the contact would write it, and a contact whose template cannot
 * hold the change, or that is already deleted, is left as it was, with the reason.
 */
class ChangeContactCategories
{
    public const int LIMIT = 500;

    /** @var list<Contact> */
    private array $changed = [];

    /** @var list<array{contact: Contact, reason: string}> */
    private array $unchanged = [];

    /** @var array<string, list<int>> */
    private array $previous = [];

    /** @param  list<array{contact: Contact, categories: list<ValueListItem>}>  $assignments */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly array $assignments,
        private readonly bool $reachesSensitiveFields = true,
    ) {}

    /** @return array{changed: list<Contact>, unchanged: list<array{contact: Contact, reason: string}>, previous: array<string, list<int>>} */
    public function execute(): array
    {
        $this->validate();
        $this->change();

        return ['changed' => $this->changed, 'unchanged' => $this->unchanged, 'previous' => $this->previous];
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if (count($this->assignments) > self::LIMIT) {
            throw new InvalidArgumentException('At most 500 contacts can be changed at once.');
        }

        $categoryListId = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->where('key', ValueListEnum::ContactCategories->value)
            ->value('id');

        foreach ($this->assignments as $assignment) {
            if ($assignment['contact']->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException('This contact is in another vault.');
            }

            foreach ($assignment['categories'] as $category) {
                if ($category->account_id !== $this->user->account_id) {
                    throw new ModelNotFoundException('This category is in another account.');
                }

                if ($category->value_list_id !== $categoryListId) {
                    throw new InvalidArgumentException("The item {$category->label} is not a contact category.");
                }
            }
        }
    }

    private function change(): void
    {
        foreach ($this->assignments as $assignment) {
            $this->changeOne($assignment['contact'], $assignment['categories']);
        }
    }

    /** @param  list<ValueListItem>  $categories */
    private function changeOne(Contact $contact, array $categories): void
    {
        if ($contact->trashed()) {
            $this->unchanged[] = ['contact' => $contact, 'reason' => __('That contact does not exist.')];

            return;
        }

        $fields = array_values(array_filter(
            new OwnedFields($contact)->categoryFields(),
            fn (Field $field): bool => $field->parent_field_id === null
                && ($this->reachesSensitiveFields || ! $field->is_sensitive),
        ));

        if ($fields === []) {
            $this->unchanged[] = ['contact' => $contact, 'reason' => __('Their template has no category field.')];

            return;
        }

        if ($categories === [] && array_filter($fields, fn (Field $field): bool => $field->is_required) !== []) {
            $this->unchanged[] = ['contact' => $contact, 'reason' => __('Their category is required.')];

            return;
        }

        $held = $contact->heldCategories(reachesSensitiveFields: $this->reachesSensitiveFields)->pluck('id')->all();
        $chosen = array_map(fn (ValueListItem $category): int => $category->id, $categories);

        $this->previous[$contact->uuid] = $held;
        $this->changed[] = $contact;

        if (collect($held)->sort()->values()->all() === collect($chosen)->sort()->values()->all()) {
            return;
        }

        DB::transaction(function () use ($contact, $fields, $categories): void {
            foreach ($fields as $index => $field) {
                if ($index === 0 && $categories !== []) {
                    new SetFieldValue(user: $this->user, valuable: $contact, field: $field, valueListItems: $categories)->execute();

                    continue;
                }

                if ($field->is_required) {
                    continue;
                }

                $this->empty($contact, $field);
            }
        });
    }

    private function empty(Contact $contact, Field $field): void
    {
        $value = FieldValue::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_id', $field->id)
            ->whereNull('field_value_row_id')
            ->first();

        if ($value === null) {
            return;
        }

        new DestroyFieldValue(user: $this->user, fieldValue: $value)->execute();
    }
}
