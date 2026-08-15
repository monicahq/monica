<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CountryEnum;
use App\Helpers\ShareLinkSummary;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use App\Models\User;
use App\Models\Vault;

/**
 * One link: what it shows, and every time it was opened.
 *
 * It is the screen somebody lands on straight after making a link, which is the
 * only moment the address can be shown at all. Only a hash of the token is
 * written down, so the address exists once, in the response to the request that
 * minted it. Coming back to this screen later is coming back to everything
 * except that: a link nobody copied is replaced rather than recovered, and the
 * screen says so instead of pretending to look it up.
 *
 * The visits are the reason the feature feels safe enough to use. What they hold
 * is deliberately coarse: the visitor never agreed to anything with us, so the
 * page answers "was this opened, roughly when, roughly from where" and stops.
 */
class ShareLinkViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ShareLink $shareLink,
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

    /**
     * What the screen says above the address, which is different the first time
     * and every time after it.
     */
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

        return __('Copy it now. This is the only time it can be shown to you in full: it is not stored anywhere in readable form, so a lost link is replaced rather than recovered.');
    }

    public function lostLine(): string
    {
        return __('The address cannot be shown again. Only a hash of it is kept, which is what stops a copy of the database from being a set of working links. If it was not copied, revoke this one and make another.');
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

    /**
     * What this link shows, said in full, with the way back to the form beside
     * each line.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function recap(): array
    {
        return [
            ['label' => __('Shows'), 'value' => ShareLinkSummary::showsLine($this->shareLink, $this->contact)],
            ['label' => __('Held back'), 'value' => ShareLinkSummary::heldBackLine($this->shareLink, $this->contact)],
            ['label' => __('Password'), 'value' => $this->shareLink->isProtectedByAPassword()
                ? __('Asked for before the page opens')
                : __('None. Anybody holding the address can open it')],
            ['label' => __('Ends'), 'value' => ShareLinkSummary::endsLine($this->shareLink)],
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

        if ($count === 1) {
            return __('Opened once');
        }

        return __('Opened :count times', ['count' => $count]);
    }

    public function visitsEmptyLine(): string
    {
        return __('Nothing has opened this address. Every visit lands here with its date, its time and roughly where from, and nothing about the person, because there is no account to know anything about.');
    }

    /**
     * @return array<int, array{when: string, where: string, agent: string, failed: bool}>
     */
    public function visits(): array
    {
        return $this->shareLink
            ->visits()
            ->limit(50)
            ->get()
            ->map(fn (ShareLinkVisit $visit): array => [
                'when' => $visit->viewed_at->isoFormat('LL, LT'),
                'where' => $this->whereFrom($visit),
                'agent' => $visit->user_agent ?? __('an unnamed browser'),
                'failed' => $visit->password_failed,
            ])
            ->all();
    }

    /**
     * The country when something resolved one, the address when it did not.
     * Nothing resolves an address to a country yet, so today this is the
     * address, and the column is there for when something does.
     */
    private function whereFrom(ShareLinkVisit $visit): string
    {
        $country = $visit->country === null ? null : CountryEnum::tryFrom(mb_strtolower($visit->country));

        if ($country !== null) {
            return __($country->label());
        }

        return $visit->ip_address ?? __('an address we did not keep');
    }
}
