<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Helpers\AvailableLocales;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Helpers\ChangelogScreenshot;
use App\Helpers\KnowledgeMarkdown;
use App\Models\ChangelogVote;
use App\Models\User;
use Carbon\CarbonImmutable;

class ChangelogViewModel
{
    /** @var array<string, true> */
    private readonly array $unseenIds;

    /** @param  list<string>  $unseenIds  the entries the person had not seen when the page was asked for */
    public function __construct(
        private readonly ?User $user,
        array $unseenIds,
        private readonly int $period = 30,
    ) {
        $this->unseenIds = array_fill_keys($unseenIds, true);
    }

    /** Votes, comments and the link into the application are for signed in people only. */
    public function allowsFeedback(): bool
    {
        return $this->user !== null;
    }

    public function currentPeriod(): int
    {
        return $this->period;
    }

    /** @return list<array{days: int, label: string, url: string, current: bool}> */
    public function periods(): array
    {
        return [
            ['days' => 30, 'label' => __('Last 30 days'), 'url' => route('changelog.index'), 'current' => $this->period === 30],
            ['days' => 90, 'label' => __('Last 3 months'), 'url' => route('changelog.index', ['period' => 90]), 'current' => $this->period === 90],
        ];
    }

    public function unseenCount(): int
    {
        return count($this->unseenIds);
    }

    /** @return array{days: int, features: int, improvements: int, fixes: int} */
    public function figures(): array
    {
        return ChangelogEntries::figures($this->entries());
    }

    /**
     * One group per UTC day, newest first: features and improvements, then the day's fixes.
     *
     * @return list<array{date: string, label: string, dateLabel: string, entries: list<array<string, mixed>>, fixes: list<array<string, mixed>>, fixesHeading: string, showsSeenDivider: bool}>
     */
    public function days(): array
    {
        $entries = $this->entries();
        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $entries);
        $commentCounts = $this->user === null ? [] : ChangelogEntries::commentCounts($ids);
        $votes = $this->user === null ? collect() : ChangelogVote::query()
            ->where('user_id', $this->user->id)
            ->whereIn('entry_id', $ids)
            ->pluck('direction', 'entry_id');

        $byDay = [];

        foreach ($entries as $entry) {
            $byDay[$entry->day()][] = [
                'entry' => $entry,
                'row' => [
                    'id' => $entry->id,
                    'typeLabel' => __($entry->type->label()),
                    'tone' => $entry->type->tone(),
                    'area' => $entry->area === null ? null : __($entry->area),
                    'title' => __($entry->title),
                    'bodyHtml' => KnowledgeMarkdown::toHtml(__($entry->body)),
                    'link' => $entry->link === null ? null : url($entry->link),
                    'screenshots' => array_map(fn (ChangelogScreenshot $screenshot): array => [
                        'url' => $screenshot->url(),
                        'description' => __($screenshot->description),
                        'width' => $screenshot->width,
                        'height' => $screenshot->height,
                    ], $entry->screenshots),
                    'isNew' => isset($this->unseenIds[$entry->id]),
                    'vote' => $votes->get($entry->id)?->value,
                    'commentCount' => $commentCounts[$entry->id] ?? 0,
                    'commentLabel' => $this->commentLabel($commentCounts[$entry->id] ?? 0),
                    'commentsUrl' => route('changelog.comments.index', ['entry' => $entry->id]),
                    'voteUrl' => route('changelog.vote.update', ['entry' => $entry->id]),
                ],
            ];
        }

        $days = [];
        $hasUnseen = $this->unseenIds !== [];
        $dividerShown = false;

        foreach ($byDay as $date => $items) {
            $fixes = array_values(array_filter($items, fn (array $item): bool => $item['entry']->isFix()));
            $others = array_values(array_filter($items, fn (array $item): bool => ! $item['entry']->isFix()));
            $dayHasUnseen = array_any($items, fn (array $item): bool => $item['row']['isNew']);
            $showsSeenDivider = $hasUnseen && ! $dividerShown && ! $dayHasUnseen;
            $dividerShown = $dividerShown || $showsSeenDivider;
            $day = CarbonImmutable::parse($date, 'UTC');

            $days[] = [
                'date' => $date,
                'label' => $this->dayLabel($day),
                'dateLabel' => $this->dateLabel($day),
                'entries' => array_column($others, 'row'),
                'fixes' => array_column($fixes, 'row'),
                'fixesHeading' => trans_choice(':count fix|:count fixes', count($fixes)),
                'showsSeenDivider' => $showsSeenDivider,
            ];
        }

        return $days;
    }

    /** @return list<ChangelogEntry> */
    private function entries(): array
    {
        return app(ChangelogEntries::class)->publishedWithin($this->period);
    }

    private function commentLabel(int $count): string
    {
        if ($count === 0) {
            return __('Comment');
        }

        return trans_choice(':count comment|:count comments', $count);
    }

    private function dayLabel(CarbonImmutable $day): string
    {
        $today = CarbonImmutable::now('UTC')->startOfDay();

        if ($day->equalTo($today)) {
            return __('Today');
        }

        if ($day->equalTo($today->subDay())) {
            return __('Yesterday');
        }

        return $day->locale(app()->getLocale())->translatedFormat('l');
    }

    private function dateLabel(CarbonImmutable $day): string
    {
        return $this->user?->date_format?->format($day)
            ?? $day->locale(app()->getLocale())->translatedFormat((string) AvailableLocales::details(app()->getLocale())['date_format']);
    }
}
