<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\NameOrderEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

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
        return __('What the template does not decide about :name.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function nameDescription(): string
    {
        return __('Every part is optional on its own, and at least one has to be filled in. This is asked for whatever template :name is on.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function action(): string
    {
        return route('contacts.name.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function avatarDescription(): string
    {
        return __('A square photo reads best. JPEG, PNG or WebP, up to 5 MB. Without one, :name keeps their initials on a coloured background.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function hasAvatar(): bool
    {
        return $this->contact->hasAvatar();
    }

    public function avatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->vault, 'file' => $this->contact->avatar]);
    }

    public function updateAvatarUrl(): string
    {
        return route('contacts.avatar.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyAvatarUrl(): string
    {
        return route('contacts.avatar.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyUrl(): string
    {
        return route('contacts.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function mayBeDeleted(): bool
    {
        return ! $this->contact->is_me;
    }

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

    public function value(string $field): ?string
    {
        return old($field, $this->contact->$field);
    }

    /** @return array<int, array{value: string, label: string, isSelected: bool}> */
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
