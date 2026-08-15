<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\ShareLinkSummary;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;

/**
 * Every link ever opened onto one person's page, including the ones that were
 * ended.
 *
 * A revoked link keeps its row on purpose. The question this screen answers is
 * who could see what, and when, and deleting the row would delete the answer.
 * So nothing here removes anything: a link is either working, finished on its
 * own, or ended by hand, and all three are part of the same history.
 *
 * The state of a link is worked out here rather than stored on it. Live, expired
 * and revoked are three readings of two nullable dates, and a screen is the only
 * thing that needs the reading.
 */
class ContactShareLinksViewModel
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
        return __('Links to :name’s page', ['name' => $this->contact->first_name ?? $this->contact->name]);
    }

    /**
     * The sentence at the top, which has to say why the ended links are still
     * on the list before somebody wonders whether revoking worked.
     */
    public function intro(): string
    {
        return __('Every link ever made for them, including the ones you ended. Kept so you can always answer the question of who could see what, and when.');
    }

    public function emptyLine(): string
    {
        return __('Nothing is shared. A link opens this one page for somebody who has no account, shows only what you let through, and stops working when you say so.');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function newUrl(): string
    {
        return route('contacts.shareLinks.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * @return array<int, array{uuid: string, label: string, meta: string, state: string, stateTone: string, shows: string, opened: string, lastSeen: string, ends: string, isLive: bool, showUrl: string, editUrl: string, revokeUrl: string, revokeLine: string}>
     */
    public function links(): array
    {
        return $this->contact
            ->shareLinks()
            ->with('createdBy')
            ->get()
            ->map(fn (ShareLink $link): array => [
                'uuid' => $link->uuid,
                'label' => $link->label,
                'meta' => $this->metaOf($link),
                'state' => $this->stateOf($link),
                'stateTone' => $this->toneOf($link),
                'shows' => ShareLinkSummary::showsLine($link, $this->contact),
                'opened' => $this->openedLine($link),
                'lastSeen' => $link->last_viewed_at === null
                    ? __('never opened')
                    : $link->last_viewed_at->diffForHumans(),
                'ends' => ShareLinkSummary::endsLine($link),
                'isLive' => $link->isUsable(),
                'showUrl' => route('contacts.shareLinks.show', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'editUrl' => route('contacts.shareLinks.edit', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'revokeUrl' => route('contacts.shareLinks.destroy', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'revokeLine' => $this->revokeLine($link),
            ])
            ->all();
    }

    /**
     * Who it was for and when it was made, which is what tells two links apart
     * when they were given the same name.
     */
    private function metaOf(ShareLink $link): string
    {
        if ($link->recipient_hint === null) {
            return __('Made :when', ['when' => $link->created_at->isoFormat('LL')]);
        }

        return __(':who · made :when', [
            'who' => $link->recipient_hint,
            'when' => $link->created_at->isoFormat('LL'),
        ]);
    }

    private function stateOf(ShareLink $link): string
    {
        if ($link->revoked_at !== null) {
            return __('Revoked');
        }

        if (! $link->isUsable()) {
            return __('Expired');
        }

        return __('Live');
    }

    private function toneOf(ShareLink $link): string
    {
        if ($link->revoked_at !== null) {
            return 'danger';
        }

        if (! $link->isUsable()) {
            return 'neutral';
        }

        return 'success';
    }

    private function openedLine(ShareLink $link): string
    {
        if ($link->view_count === 0) {
            return __('Not opened');
        }

        if ($link->view_count === 1) {
            return __('Opened once');
        }

        return __('Opened :count times', ['count' => $link->view_count]);
    }

    /**
     * What the confirmation has to say before it ends a link: what stops
     * working, and what stays.
     */
    private function revokeLine(ShareLink $link): string
    {
        if ($link->view_count === 0) {
            return __('The address stops working straight away, and it was never opened. The row stays on this list, marked revoked. A revoked link is never brought back, so somebody who needs access again needs a new one.');
        }

        return __('The address stops working straight away. It was opened :count times, last :when. The row stays on this list with those visits, marked revoked. A revoked link is never brought back, so somebody who needs access again needs a new one.', [
            'count' => $link->view_count,
            'when' => $link->last_viewed_at?->diffForHumans() ?? __('at some point'),
        ]);
    }
}
