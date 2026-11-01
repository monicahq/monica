<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;

class ContactGroupsFormViewModel
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
        return __('Groups of :name', ['name' => $this->contact->name]);
    }

    /**
     * Every group of the vault by name, ticked when the contact is in it today.
     *
     * @return list<array{uuid: string, name: string, isChosen: bool}>
     */
    public function groups(): array
    {
        $chosen = old('groups');

        $current = GroupMember::query()
            ->where('contact_id', $this->contact->id)
            ->whereNull('left_on')
            ->pluck('group_id');

        return Group::query()
            ->where('vault_id', $this->vault->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group): array => [
                'uuid' => $group->uuid,
                'name' => $group->name,
                'isChosen' => is_array($chosen) ? in_array($group->uuid, $chosen, true) : $current->contains($group->id),
            ])
            ->values()
            ->all();
    }

    public function action(): string
    {
        return route('contacts.groups.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function newGroupUrl(): string
    {
        return route('contacts.groups.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', ['vault' => $this->vault]);
    }
}
