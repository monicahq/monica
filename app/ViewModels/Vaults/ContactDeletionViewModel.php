<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactDeletionViewModel
{
    /** @var list<Contact>|null */
    private ?array $deletable = null;

    /** @param  list<Contact>  $contacts */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly array $contacts,
    ) {}

    public function holdsAnybodyToDelete(): bool
    {
        return $this->deletable() !== [];
    }

    public function title(): string
    {
        $deletable = $this->deletable();

        if ($deletable === []) {
            return '';
        }

        return trans_choice('{1}Delete :name?|Delete :count people?', count($deletable), ['name' => $deletable[0]->name]);
    }

    public function names(): string
    {
        $names = array_map(fn (Contact $contact): string => $contact->name, $this->deletable());

        return match (count($names)) {
            0 => '',
            1 => $names[0],
            2 => __(':first and :second', ['first' => $names[0], 'second' => $names[1]]),
            3 => __(':first, :second and :third', ['first' => $names[0], 'second' => $names[1], 'third' => $names[2]]),
            default => trans_choice(
                ':first, :second and :count other|:first, :second and :count others',
                count($names) - 2,
                ['first' => $names[0], 'second' => $names[1]],
            ),
        };
    }

    public function consequence(): string
    {
        return __('Their relationships, reminders and life events go with them to the trash of this vault, where they wait 30 days and can be restored.');
    }

    public function keptLine(): ?string
    {
        $reasons = collect($this->contacts)
            ->reject(fn (Contact $contact): bool => $contact->trashed())
            ->map(fn (Contact $contact): ?string => $this->user->inspect('delete', $contact)->message())
            ->filter()
            ->unique()
            ->values();

        return $reasons->isEmpty() ? null : $reasons->implode(' ');
    }

    public function confirm(): string
    {
        $deletable = $this->deletable();

        if ($deletable === []) {
            return '';
        }

        return trans_choice('{1}Delete the contact|Delete :count people', count($deletable));
    }

    public function action(): string
    {
        return route('contacts.deletion.destroy', $this->vault);
    }

    /** @return list<string> */
    public function uuids(): array
    {
        return array_map(fn (Contact $contact): string => $contact->uuid, $this->deletable());
    }

    /** @return list<Contact> */
    private function deletable(): array
    {
        return $this->deletable ??= array_values(array_filter(
            $this->contacts,
            fn (Contact $contact): bool => ! $contact->trashed() && $this->user->can('delete', $contact),
        ));
    }
}
