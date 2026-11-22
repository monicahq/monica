<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

/**
 * The people of the vault a loan could be made with, answering what somebody typed in its contact
 * search, and the offer to write somebody new down when nobody fits.
 */
class PossessionLoanContactCandidatesViewModel
{
    public const int CANDIDATE_LIMIT = 10;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $query,
    ) {}

    /** @return list<array{uuid: string, name: string}> */
    public function candidates(): array
    {
        return once(fn (): array => Contact::query()
            ->where('vault_id', $this->vault->id)
            ->matchingSearch($this->query, $this->user->locale)
            ->orderBy('sort_key')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Contact $contact): array => ['uuid' => $contact->uuid, 'name' => $contact->name])
            ->values()
            ->all());
    }

    public function noCandidatesLine(): string
    {
        if (mb_trim($this->query) === '') {
            return __('Nobody is in this vault yet.');
        }

        return __('Nobody called “:query” in your contacts.', ['query' => mb_trim($this->query)]);
    }

    public function createLabel(): string
    {
        if (mb_trim($this->query) === '') {
            return __('Create a new contact');
        }

        return __('Create “:query” as a new contact', ['query' => mb_trim($this->query)]);
    }

    public function query(): string
    {
        return mb_trim($this->query);
    }
}
