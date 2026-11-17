<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\ChangelogVoteDirectionEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Models\ChangelogComment;
use App\Models\ChangelogVote;

class ChangelogVotesViewModel
{
    public const array SORTS = ['down', 'up', 'new'];

    public function __construct(
        private readonly string $sort = 'down',
    ) {}

    public static function sortFrom(?string $requested): string
    {
        return in_array($requested, self::SORTS, true) ? $requested : 'down';
    }

    public function sort(): string
    {
        return $this->sort;
    }

    /** @return list<array{value: string, label: string}> */
    public function sortOptions(): array
    {
        return [
            ['value' => 'down', 'label' => __('Most disliked')],
            ['value' => 'up', 'label' => __('Most liked')],
            ['value' => 'new', 'label' => __('Newest first')],
        ];
    }

    /** @return list<array{key: string, label: string, url: string, selected: bool, count: int|null}> */
    public function tabs(): array
    {
        return array_map(fn (array $tab): array => [...$tab, 'selected' => $tab['key'] === 'votes'], new ChangelogModerationViewModel()->tabs());
    }

    public function summary(): string
    {
        $rows = $this->rows();
        $votes = array_sum(array_column($rows, 'up')) + array_sum(array_column($rows, 'down'));

        return trans_choice(':count vote on :changes published in the last 30 days.|:count votes on :changes published in the last 30 days.', $votes, [
            'changes' => trans_choice(':count change|:count changes', count($rows)),
        ]);
    }

    /**
     * The changes of the last 30 days with their votes, in the order asked for.
     *
     * @return list<array{id: string, title: string, typeLabel: string, tone: string, area: string|null, published: string, up: int, down: int, upShare: int, shareLabel: string, commentCount: int, commentLabel: string, commentsUrl: string}>
     */
    public function rows(): array
    {
        $entries = app(ChangelogEntries::class)->publishedWithin(30);
        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $entries);
        $comments = ChangelogComment::query()->whereIn('entry_id', $ids)->selectRaw('entry_id, count(*) as aggregate')->groupBy('entry_id')->pluck('aggregate', 'entry_id');
        $votes = [];

        foreach (ChangelogVote::query()->whereIn('entry_id', $ids)->selectRaw('entry_id, direction, count(*) as aggregate')->groupBy('entry_id', 'direction')->get() as $row) {
            $votes[$row->entry_id][$row->direction === ChangelogVoteDirectionEnum::Up ? 'up' : 'down'] = (int) $row->getAttribute('aggregate');
        }

        $rows = array_map(function (ChangelogEntry $entry) use ($votes, $comments): array {
            $up = $votes[$entry->id]['up'] ?? 0;
            $down = $votes[$entry->id]['down'] ?? 0;
            $upShare = $up + $down === 0 ? 0 : (int) round($up / ($up + $down) * 100);
            $commentCount = (int) ($comments[$entry->id] ?? 0);

            return [
                'id' => $entry->id,
                'title' => __($entry->title),
                'typeLabel' => __($entry->type->label()),
                'tone' => $entry->type->tone(),
                'area' => $entry->area === null ? null : __($entry->area),
                'published' => $entry->publishedAt->locale(app()->getLocale())->translatedFormat((string) AvailableLocales::details(app()->getLocale())['date_format']),
                'publishedAt' => $entry->publishedAt->getTimestamp(),
                'up' => $up,
                'down' => $down,
                'upShare' => $upShare,
                'shareLabel' => __(':share% like it', ['share' => $upShare]),
                'commentCount' => $commentCount,
                'commentLabel' => trans_choice(':count comment|:count comments', $commentCount),
                'commentsUrl' => route('admin.changelog.index', ['tab' => 'all', 'period' => 'all', 'entry' => $entry->id]),
            ];
        }, $entries);

        usort($rows, fn (array $a, array $b): int => match ($this->sort) {
            'up' => $b['up'] <=> $a['up'],
            'new' => $b['publishedAt'] <=> $a['publishedAt'],
            default => $b['down'] <=> $a['down'],
        });

        return array_map(function (array $row): array {
            unset($row['publishedAt']);

            return $row;
        }, $rows);
    }
}
