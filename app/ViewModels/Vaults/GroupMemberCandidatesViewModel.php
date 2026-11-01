<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;

class GroupMemberCandidatesViewModel
{
    public const int CANDIDATE_LIMIT = 25;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly string $query,
    ) {}

    /**
     * The people of the vault matching what was typed, leaving out those already in the group today.
     *
     * @return list<array{uuid: string, name: string}>
     */
    public function candidates(): array
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->whereDoesntHave('groupMembers', fn ($query) => $query->where('group_id', $this->group->id)->whereNull('left_on'))
            ->matchingSearch($this->query, $this->user->locale)
            ->orderBy('sort_name')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Contact $contact): array => ['uuid' => $contact->uuid, 'name' => $contact->name])
            ->values()
            ->all();
    }

    public function noCandidatesLine(): string
    {
        if (mb_trim($this->query) === '') {
            return __('Nobody in this vault is left to add.');
        }

        return __('Nobody in this vault matches “:query”.', ['query' => $this->query]);
    }
}
