<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\Paginator;

class ContactDuplicatesViewModel
{
    private const int PER_PAGE = 25;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('People who might be the same person');
    }

    public function emptyLine(): string
    {
        return __('Nobody in this vault looks like a duplicate of anybody else.');
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function searchUrl(): string
    {
        return route('vaults.duplicates.search.create', $this->vault);
    }

    /** @return Paginator<int, ContactDuplicateCandidate> */
    public function pairs(): Paginator
    {
        return once(fn (): Paginator => ContactDuplicateCandidate::query()
            ->standing()
            ->whereBothContactsExist()
            ->where('vault_id', $this->vault->id)
            ->with(['contact', 'otherContact'])
            ->orderByDesc('score')
            ->orderBy('id')
            ->simplePaginate(self::PER_PAGE));
    }

    /** @return array{name: string, other_name: string, why: string, compare_url: string, dismiss_url: string} */
    public function row(ContactDuplicateCandidate $candidate): array
    {
        return [
            'name' => $candidate->contact->name,
            'other_name' => $candidate->otherContact->name,
            'why' => $this->why($candidate),
            'compare_url' => route('contacts.merge.show', [
                'vault' => $this->vault,
                'contact' => $candidate->contact,
                'other' => $candidate->otherContact,
            ]),
            'dismiss_url' => route('vaults.duplicates.dismissal.update', [
                'vault' => $this->vault,
                'candidate' => $candidate->id,
            ]),
        ];
    }

    private function why(ContactDuplicateCandidate $candidate): string
    {
        $said = [];

        foreach ($candidate->reasons as $reason) {
            $said[] = $reason['kind'] === 'field'
                ? __('they share :value', ['value' => $reason['value']])
                : __('their names are alike');
        }

        return implode(__(', '), array_unique($said));
    }
}
