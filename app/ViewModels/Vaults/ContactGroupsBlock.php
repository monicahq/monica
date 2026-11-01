<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;

class ContactGroupsBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    /**
     * The groups the contact is in today, then those they left, the most recent departure first. A
     * deleted group is left out until it is restored.
     *
     * @return list<array{name: string, url: string, role: string|null, period: string|null, isCurrent: bool}>
     */
    public function memberships(): array
    {
        return once(function (): array {
            $period = new GroupPeriod($this->user);

            return GroupMember::query()
                ->where('contact_id', $this->contact->id)
                ->whereHas('group', fn (Builder $query): Builder => $query->where('vault_id', $this->vault->id))
                ->with('group')
                ->orderByRaw('case when left_on is null then 0 else 1 end')
                ->orderByDesc('left_on')
                ->orderBy('id')
                ->get()
                ->map(fn (GroupMember $membership): array => [
                    'name' => $membership->group->name,
                    'url' => route('groups.show', ['vault' => $this->vault, 'group' => $membership->group]),
                    'role' => $membership->role,
                    'period' => $period->between($membership->joined_on, $membership->joined_precision, $membership->left_on, $membership->left_precision),
                    'isCurrent' => $membership->isCurrent(),
                ])
                ->values()
                ->all();
        });
    }

    public function emptyLine(): string
    {
        return __(':name is in no group yet.', ['name' => $this->contact->name]);
    }

    public function groupsUrl(): string
    {
        return route('contacts.groups.edit', ['vault' => $this->vault, 'contact' => $this->contact]);
    }
}
