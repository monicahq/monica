<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\ChangelogCommentStatusEnum;
use App\Enums\ChangelogEntryTypeEnum;
use App\Enums\ChangelogVoteDirectionEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use App\Models\ChangelogVote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ChangelogModerationViewModel
{
    public const array TABS = ['review', 'private', 'hidden', 'all'];

    public const array PERIODS = ['30', '90', 'all'];

    /** @var Collection<int, ChangelogComment>|null */
    private ?Collection $matches = null;

    public function __construct(
        private readonly ?User $viewer = null,
        private readonly string $tab = 'review',
        private readonly ?string $entry = null,
        private readonly string $search = '',
        private readonly string $type = '',
        private readonly string $visibility = '',
        private readonly string $period = '30',
    ) {}

    public static function tabFrom(?string $requested): string
    {
        return in_array($requested, self::TABS, true) ? $requested : 'review';
    }

    public static function periodFrom(?string $requested): string
    {
        return in_array($requested, self::PERIODS, true) ? $requested : '30';
    }

    public function tab(): string
    {
        return $this->tab;
    }

    /** @return array{search: string, type: string, visibility: string, period: string} */
    public function filters(): array
    {
        return ['search' => $this->search, 'type' => $this->type, 'visibility' => $this->visibility, 'period' => $this->period];
    }

    /** @return list<array{key: string, label: string, url: string, selected: bool, count: int|null}> */
    public function tabs(): array
    {
        $tabs = [];

        foreach (['review' => __('To review'), 'private' => __('Private'), 'hidden' => __('Hidden'), 'all' => __('All comments')] as $key => $label) {
            $tabs[] = [
                'key' => $key,
                'label' => $label,
                'url' => route('admin.changelog.index', ['tab' => $key]),
                'selected' => $this->tab === $key,
                'count' => $this->tabQuery(ChangelogComment::query(), $key)->count(),
            ];
        }

        $tabs[] = ['key' => 'votes', 'label' => __('Votes'), 'url' => route('admin.changelog.votes.index'), 'selected' => false, 'count' => null];

        return $tabs;
    }

    /** @return list<array{value: string, label: string}> */
    public function typeOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All types')],
            ...array_map(fn (ChangelogEntryTypeEnum $type): array => ['value' => $type->value, 'label' => __($type->label())], ChangelogEntryTypeEnum::cases()),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function visibilityOptions(): array
    {
        return [
            ['value' => '', 'label' => __('Public and private')],
            ['value' => 'public', 'label' => __('Public')],
            ['value' => 'private', 'label' => __('Private')],
            ['value' => 'anonymous', 'label' => __('Anonymous')],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function periodOptions(): array
    {
        return [
            ['value' => '30', 'label' => __('Last 30 days')],
            ['value' => '90', 'label' => __('Last 3 months')],
            ['value' => 'all', 'label' => __('Any time')],
        ];
    }

    /**
     * The changes holding at least one matching comment, newest first.
     *
     * @return list<array{id: string, title: string, meta: string, day: string, up: int, down: int, matchLabel: string, url: string, current: bool}>
     */
    public function rows(): array
    {
        $counts = $this->matches()->countBy('entry_id');
        $votes = $this->voteCounts($counts->keys()->all());
        $current = $this->currentEntry();
        $rows = [];

        foreach ($this->entries() as $entry) {
            if (! $counts->has($entry->id)) {
                continue;
            }

            $rows[] = [
                'id' => $entry->id,
                'title' => __($entry->title),
                'meta' => $entry->area === null ? __($entry->type->label()) : __($entry->type->label()).' · '.__($entry->area),
                'day' => $this->dateOf($entry),
                'up' => $votes[$entry->id]['up'] ?? 0,
                'down' => $votes[$entry->id]['down'] ?? 0,
                'matchLabel' => trans_choice(':count comment|:count comments', $counts->get($entry->id)),
                'url' => route('admin.changelog.index', [...$this->query(), 'entry' => $entry->id]),
                'current' => $current?->id === $entry->id,
            ];
        }

        return $rows;
    }

    public function countLine(): string
    {
        return trans_choice(':count comment on :changes|:count comments on :changes', $this->matches()->count(), [
            'changes' => trans_choice(':count change|:count changes', count($this->rows())),
        ]);
    }

    public function emptyTitle(): string
    {
        return match ($this->tab) {
            'review' => __('Nothing to review'),
            'private' => __('No private comments'),
            'hidden' => __('Nothing hidden'),
            default => __('No comments yet'),
        };
    }

    public function emptyBody(): string
    {
        return match ($this->tab) {
            'review' => __('New comments on What’s new land here until somebody marks them as reviewed.'),
            'private' => __('Comments people send only to the team land here.'),
            'hidden' => __('Comments you hide from What’s new land here, and you can show them again.'),
            default => __('Comments people leave on What’s new land here.'),
        };
    }

    /** @return array{id: string, title: string, typeLabel: string, tone: string, meta: string, file: string, up: int, down: int, upShare: int, ratioLine: string, commentSummary: string}|null */
    public function current(): ?array
    {
        $entry = $this->currentEntry();

        if (! $entry instanceof ChangelogEntry) {
            return null;
        }

        $votes = $this->voteCounts([$entry->id])[$entry->id] ?? ['up' => 0, 'down' => 0];
        $total = $votes['up'] + $votes['down'];
        $upShare = $total === 0 ? 0 : (int) round($votes['up'] / $total * 100);
        $public = ChangelogComment::query()->where('entry_id', $entry->id)->shownToEverybody()->count();
        $private = ChangelogComment::query()->where('entry_id', $entry->id)->where('is_private', true)->count();

        return [
            'id' => $entry->id,
            'title' => __($entry->title),
            'typeLabel' => __($entry->type->label()),
            'tone' => $entry->type->tone(),
            'meta' => ($entry->area === null ? '' : __($entry->area).' · ').__('Published :date', ['date' => $this->dateOf($entry)]),
            'file' => "changelog/{$entry->file}",
            'up' => $votes['up'],
            'down' => $votes['down'],
            'upShare' => $upShare,
            'ratioLine' => $total === 0
                ? __('Nobody has voted yet.')
                : trans_choice(':share% of :count person who voted likes this change.|:share% of :count people who voted like this change.', $total, ['share' => $upShare]),
            'commentSummary' => trans_choice(':count public comment|:count public comments', $public).', '.trans_choice(':count private comment|:count private comments', $private),
        ];
    }

    /**
     * The matching comments on the change being looked at, with their real author: administrators
     * see who wrote an anonymous comment.
     *
     * @return list<array<string, mixed>>
     */
    public function comments(): array
    {
        $entry = $this->currentEntry();

        if (! $entry instanceof ChangelogEntry) {
            return [];
        }

        return $this->matches()
            ->where('entry_id', $entry->id)
            ->map(fn (ChangelogComment $comment): array => [
                'id' => $comment->uuid,
                'author' => $comment->user->name,
                'who' => $comment->is_anonymous
                    ? __(':email · Account #:account · Shown as Anonymous on What’s new', ['email' => $comment->user->email, 'account' => $comment->user->account_id])
                    : __(':email · Account #:account', ['email' => $comment->user->email, 'account' => $comment->user->account_id]),
                'isAnonymous' => $comment->is_anonymous,
                'isPrivate' => $comment->is_private,
                'isNew' => $comment->status === ChangelogCommentStatusEnum::New,
                'isHidden' => $comment->status === ChangelogCommentStatusEnum::Hidden,
                'body' => $comment->body,
                'createdAt' => $comment->created_at,
                'replies' => $comment->replies->map(fn (ChangelogCommentReply $reply): array => [
                    'author' => $reply->author_name,
                    'kind' => $reply->is_public ? __('Public reply under the comment') : __('Reply sent by email'),
                    'body' => $reply->body,
                    'createdAt' => $reply->created_at,
                ])->all(),
                'replyLabel' => $comment->is_private ? __('Reply to :name', ['name' => $comment->user->first_name]) : __('Public reply'),
                'replyHelp' => $comment->is_private
                    ? __('Sent to :email by email, signed with your name. Nobody else sees it.', ['email' => $comment->user->email])
                    : __('Shown under the comment on What’s new for everyone, signed with your name and the Administrator badge.'),
                'reviewUrl' => route('admin.changelog.comments.review.create', ['comment' => $comment->uuid]),
                'hideRefusal' => $this->viewer?->inspect('hide', $comment)->message(),
                'hidingUrl' => route('admin.changelog.comments.hiding.create', ['comment' => $comment->uuid]),
                'replyUrl' => route('admin.changelog.comments.replies.create', ['comment' => $comment->uuid]),
            ])
            ->values()
            ->all();
    }

    public function whatsNewUrl(): string
    {
        return route('changelog.index', ['period' => 90]);
    }

    /** @return array<string, string> */
    public function query(): array
    {
        return array_filter([
            'tab' => $this->tab,
            'search' => $this->search,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'period' => $this->period,
        ], fn (string $value): bool => $value !== '');
    }

    private function currentEntry(): ?ChangelogEntry
    {
        $ids = $this->matches()->pluck('entry_id')->unique();

        foreach ($this->entries() as $entry) {
            if ($ids->contains($entry->id) && ($this->entry === null || $this->entry === $entry->id)) {
                return $entry;
            }
        }

        foreach ($this->entries() as $entry) {
            if ($ids->contains($entry->id)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * The entries the filters keep, newest first.
     *
     * @return list<ChangelogEntry>
     */
    private function entries(): array
    {
        $since = $this->period === 'all' ? null : CarbonImmutable::now('UTC')->subDays((int) $this->period);

        return array_values(array_filter(
            app(ChangelogEntries::class)->visible(),
            fn (ChangelogEntry $entry): bool => ($since === null || $entry->publishedAt->greaterThanOrEqualTo($since))
                && ($this->type === '' || $entry->type->value === $this->type),
        ));
    }

    /** @return Collection<int, ChangelogComment> */
    private function matches(): Collection
    {
        if ($this->matches instanceof Collection) {
            return $this->matches;
        }

        $entries = $this->entries();
        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $entries);
        $search = mb_trim($this->search);
        $titleMatches = $search === '' ? [] : array_map(
            fn (ChangelogEntry $entry): string => $entry->id,
            array_filter($entries, fn (ChangelogEntry $entry): bool => mb_stripos(__($entry->title), $search) !== false),
        );

        $query = $this->tabQuery(ChangelogComment::query(), $this->tab)
            ->with(['user', 'replies'])
            ->whereIn('entry_id', $ids);

        match ($this->visibility) {
            'public' => $query->where('is_private', false),
            'private' => $query->where('is_private', true),
            'anonymous' => $query->where('is_anonymous', true),
            default => null,
        };

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search, $titleMatches): void {
                $query->where('body', 'like', "%{$search}%")
                    ->orWhereIn('entry_id', $titleMatches)
                    ->orWhereHas('user', fn (Builder $query) => $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $this->matches = $query->orderBy('id')->get();
    }

    /**
     * @param  Builder<ChangelogComment>  $query
     * @return Builder<ChangelogComment>
     */
    private function tabQuery(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'review' => $query->where('status', ChangelogCommentStatusEnum::New->value),
            'private' => $query->where('is_private', true)->where('status', '!=', ChangelogCommentStatusEnum::Hidden->value),
            'hidden' => $query->where('status', ChangelogCommentStatusEnum::Hidden->value),
            default => $query,
        };
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, array{up: int, down: int}>
     */
    private function voteCounts(array $ids): array
    {
        $counts = [];

        $rows = ChangelogVote::query()
            ->whereIn('entry_id', $ids)
            ->selectRaw('entry_id, direction, count(*) as aggregate')
            ->groupBy('entry_id', 'direction')
            ->get();

        foreach ($rows as $row) {
            $key = $row->direction === ChangelogVoteDirectionEnum::Up ? 'up' : 'down';
            $counts[$row->entry_id][$key] = (int) $row->getAttribute('aggregate');
        }

        return $counts;
    }

    private function dateOf(ChangelogEntry $entry): string
    {
        return $entry->publishedAt->locale(app()->getLocale())->translatedFormat((string) AvailableLocales::details(app()->getLocale())['date_format']);
    }
}
