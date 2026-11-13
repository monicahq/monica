<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\PageSize;
use App\Helpers\ShareLinkSummary;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactShareLinksViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
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

    public function title(): string
    {
        return __('Share :name’s contact details', ['name' => $this->contact->first_name ?? $this->contact->name]);
    }

    public function intro(): string
    {
        return __('Choose which details to share, then send a link. Anyone with the link can view those details without a :app account.', ['app' => config('app.name')]);
    }

    public function emptyLine(): string
    {
        return __('You control what each link shows and can turn off access at any time.');
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
     * @return LengthAwarePaginator<int, array{uuid: string, label: string, meta: string, state: string, stateTone: string, shows: string, opened: string, lastSeen: string, ends: string, isLive: bool, showUrl: string, editUrl: string, revokeUrl: string, revokeLine: string}>
     */
    public function links(): LengthAwarePaginator
    {
        return PageSize::paginate($this->contact->shareLinks()->with('createdBy'), $this->size)
            ->through(fn (ShareLink $link): array => [
                'uuid' => $link->uuid,
                'label' => $link->label,
                'meta' => $this->metaOf($link),
                'state' => $this->stateOf($link),
                'stateTone' => $this->toneOf($link),
                'shows' => ShareLinkSummary::showsLine($link, $this->contact),
                'opened' => $this->openedLine($link),
                'lastSeen' => $this->lastSeenLine($link),
                'ends' => ShareLinkSummary::endsLine($link, $this->user),
                'isLive' => $link->isUsable(),
                'showUrl' => route('contacts.shareLinks.show', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'editUrl' => route('contacts.shareLinks.edit', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'revokeUrl' => route('contacts.shareLinks.destroy', ['vault' => $this->vault, 'contact' => $this->contact, 'shareLink' => $link]),
                'revokeLine' => $this->revokeLine($link),
            ]);
    }

    private function metaOf(ShareLink $link): string
    {
        if ($link->recipient_hint === null) {
            return __('Made :when', ['when' => $link->created_at->setTimezone($this->user->timezone)->isoFormat('LL')]);
        }

        return __(':who · made :when', [
            'who' => $link->recipient_hint,
            'when' => $link->created_at->setTimezone($this->user->timezone)->isoFormat('LL'),
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

    private function lastSeenLine(ShareLink $link): string
    {
        if ($link->last_viewed_at === null) {
            return __('never opened');
        }

        return $link->last_viewed_at->diffForHumans();
    }

    private function openedLine(ShareLink $link): string
    {
        if ($link->view_count === 0) {
            return __('Not opened');
        }

        return trans_choice('{1}Opened once|Opened :count times', $link->view_count);
    }

    private function revokeLine(ShareLink $link): string
    {
        if ($link->view_count === 0) {
            return __('The address stops working straight away, and it was never opened. The row stays on this list, marked revoked. A revoked link never works again, so somebody who needs access needs a new one.');
        }

        return trans_choice(
            'The address stops working straight away. It was opened :count time, last :when. The row stays on this list with those visits, marked revoked. A revoked link never works again, so somebody who needs access needs a new one.|The address stops working straight away. It was opened :count times, last :when. The row stays on this list with those visits, marked revoked. A revoked link never works again, so somebody who needs access needs a new one.',
            $link->view_count,
            ['when' => $link->last_viewed_at?->diffForHumans() ?? __('at some point')],
        );
    }
}
