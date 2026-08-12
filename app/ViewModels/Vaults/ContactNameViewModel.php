<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\NameOrderEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

/**
 * The form that changes what somebody is called.
 *
 * It asks for the same parts the form that wrote them down asked for, and for
 * the same reason: the name is structured by the product and never by a
 * template, since the product sorts, searches and builds initials out of it and
 * none of that can depend on a field somebody might delete.
 *
 * What was typed wins over what is stored, so a form that came back rejected
 * shows the words somebody wrote rather than quietly reverting them.
 */
class ContactNameViewModel
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
        return __('Change the name');
    }

    public function description(): string
    {
        return __('Every part is optional on its own, and at least one has to be there. It is the one thing the product asks for whatever template :name is on.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function action(): string
    {
        return route('contacts.name.update', ['vault' => $this->vault, 'contact' => $this->contact]);
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
