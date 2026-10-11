<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FlagState;
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

    public function uploadsAreOn(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
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
        return $this->user->can('delete', $this->contact);
    }

    public function deleteDescription(): string
    {
        $response = $this->user->inspect('delete', $this->contact);

        return $response->denied()
            ? (string) $response->message()
            : __('Taking them off the list can be undone.');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function nameEditor(): ContactNameEditorBlock
    {
        return new ContactNameEditorBlock(contact: $this->contact);
    }

    public function shortName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }

    public function value(string $field): ?string
    {
        return old($field, $this->contact->$field);
    }
}
