<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\NameOrderEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

/**
 * The screen for the two things about a contact the template has no say over:
 * what they are called, and whether they are on the record at all.
 *
 * Both are here because neither is a field. Every other question on a record
 * comes from a structure somebody chose; the name is there whatever they choose,
 * since the product sorts, searches and builds initials out of it, and deletion
 * is not a question about the person at all.
 *
 * They sit on one screen rather than two because they are the same errand:
 * somebody who came to correct a record and somebody who came to be rid of it
 * both came here from the same button, and a product that hides deletion behind
 * a third place is a product people cannot leave.
 *
 * What was typed wins over what is stored, so a form that came back rejected
 * shows the words somebody wrote rather than quietly reverting them.
 */
class ContactEditViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return __('Edit contact');
    }

    public function description(): string
    {
        return __('The two things about :name the template has no say over.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function nameDescription(): string
    {
        return __('Every part is optional on its own, and at least one has to be there. It is the one thing the product asks for whatever template :name is on.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function action(): string
    {
        return route('contacts.name.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyUrl(): string
    {
        return route('contacts.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * Whether this contact may be deleted at all. The one representing whoever
     * is looking may not: relationships and activities are written from its
     * point of view, and a vault that lost it would be talking about nobody.
     */
    public function mayBeDeleted(): bool
    {
        return ! $this->contact->is_me;
    }

    /**
     * What deleting them takes, and what it does not. It is said before the
     * warning rather than inside it, so somebody reads it while deciding rather
     * than while confirming.
     */
    public function deleteDescription(): string
    {
        return $this->mayBeDeleted()
            ? __('Taking them off the list can be undone.')
            : __('This is the contact that represents you, and it stays. Everything on the record is written from its point of view.');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * What each box starts holding: what somebody typed if the form came back,
     * and what is stored otherwise.
     */
    public function value(string $field): ?string
    {
        return old($field, $this->contact->$field);
    }

    /**
     * The orders a name may read in, with the one this contact reads in marked.
     * A contact that names none follows the preference of whoever is looking,
     * which is what the empty option says.
     *
     * @return array<int, array{value: string, label: string, isSelected: bool}>
     */
    public function nameOrders(): array
    {
        $current = old('name_order', $this->contact->name_order?->value);

        return array_map(fn (NameOrderEnum $order): array => [
            'value' => $order->value,
            'label' => __($order->label()),
            'isSelected' => $current === $order->value,
        ], NameOrderEnum::cases());
    }

    public function followsThePreference(): bool
    {
        return old('name_order', $this->contact->name_order?->value) === null;
    }
}
