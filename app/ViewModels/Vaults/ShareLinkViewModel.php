<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CountryEnum;
use App\Helpers\PageSize;
use App\Helpers\ShareLinkSummary;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;

class ShareLinkViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ShareLink $shareLink,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function shareLink(): ShareLink
    {
        return $this->shareLink;
    }

    public function title(): string
    {
        return $this->shareLink->label;
    }

    public function isLive(): bool
    {
        return $this->shareLink->isUsable();
    }

    public function heading(): string
    {
        if (! $this->isLive()) {
            return __('This link no longer works');
        }

        return __('The link is live');
    }

    public function intro(): string
    {
        if ($this->shareLink->revoked_at !== null) {
            return __('It was revoked :when, and revoking cannot be undone. Somebody who needs access again needs a new link.', [
                'when' => $this->shareLink->revoked_at->diffForHumans(),
            ]);
        }

        if (! $this->isLive()) {
            return __('It stopped working on its own, on the date you gave it. Make a new one if it is still needed.');
        }

        return __('Copy it now. This is the only time it can be shown in full. It is not stored in readable form, so a lost link is replaced rather than recovered.');
    }

    public function lostLine(): string
    {
        return __('The address cannot be shown again. Only a hash of it is kept, so a copy of the database is not a set of working links. If you did not copy it, revoke this one and make another.');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function linksUrl(): string
    {
        return route('contacts.shareLinks.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function editUrl(): string
    {
        return route('contacts.shareLinks.edit', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'shareLink' => $this->shareLink,
        ]);
    }

    public function revokeUrl(): string
    {
        return route('contacts.shareLinks.destroy', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'shareLink' => $this->shareLink,
        ]);
    }

    /** @return array<int, array{label: string, value: string}> */
    public function recap(): array
    {
        return [
            ['label' => __('Shows'), 'value' => ShareLinkSummary::showsLine($this->shareLink, $this->contact)],
            ['label' => __('Hidden'), 'value' => ShareLinkSummary::heldBackLine($this->shareLink, $this->contact)],
            ['label' => __('Password'), 'value' => $this->shareLink->isProtectedByAPassword()
                ? __('Asked for before the page opens')
                : __('None. Anybody holding the address can open it')],
            ['label' => __('Ends'), 'value' => ShareLinkSummary::endsLine($this->shareLink, $this->user)],
            ['label' => __('For'), 'value' => $this->shareLink->recipient_hint ?? __('Nobody in particular')],
            ['label' => __('Made'), 'value' => $this->madeLine()],
        ];
    }

    private function madeLine(): string
    {
        $maker = $this->shareLink->createdBy;

        if ($maker === null) {
            return __('by somebody since removed, :when', ['when' => $this->shareLink->created_at->diffForHumans()]);
        }

        return __('by :who, :when', [
            'who' => $maker->is($this->user) ? __('you') : $maker->name,
            'when' => $this->shareLink->created_at->diffForHumans(),
        ]);
    }

    public function visitsHeader(): string
    {
        $count = $this->shareLink->view_count;

        if ($count === 0) {
            return __('Not opened yet');
        }

        return trans_choice('{1}Opened once|Opened :count times', $count);
    }

    public function visitsEmptyLine(): string
    {
        return __('Nothing has opened this address. Every visit appears here with its date, its time and roughly where it came from. There is no account behind the link, so nothing about the person is recorded.');
    }

    /** @return LengthAwarePaginator<int, array{when: string, where: string, agent: string, failed: bool}> */
    public function visits(): LengthAwarePaginator
    {
        return PageSize::paginate($this->shareLink->visits(), $this->size)
            ->through(fn (ShareLinkVisit $visit): array => [
                'when' => $visit->viewed_at->setTimezone($this->user->timezone)->isoFormat('LL, LT'),
                'where' => $this->whereFrom($visit),
                'agent' => $this->agentOf($visit),
                'failed' => $visit->password_failed,
            ]);
    }

    private function agentOf(ShareLinkVisit $visit): string
    {
        return $visit->user_agent ?? __('an unnamed browser');
    }

    private function whereFrom(ShareLinkVisit $visit): string
    {
        $country = $visit->country === null ? null : CountryEnum::tryFrom(mb_strtolower($visit->country));

        if ($country !== null) {
            return __($country->label());
        }

        return $visit->ip_address ?? __('an address we did not keep');
    }
}
