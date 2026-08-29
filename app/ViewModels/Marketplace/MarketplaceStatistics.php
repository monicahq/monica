<?php

declare(strict_types=1);

namespace App\ViewModels\Marketplace;

use App\Enums\MarketplaceListingStatusEnum;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The band across the top of the catalogue: how much is in it, how much of it
 * people take away, how many people fill it, and in how many languages.
 *
 * It is there because a catalogue with no sense of scale reads as abandoned. A
 * screen that says nothing about how many listings exist looks the same whether
 * there are four of them or four hundred, and somebody arriving has no way to
 * tell whether it is worth searching.
 *
 * Every number is counted rather than stored. None of them is on a hot path,
 * they are all cheap aggregates, and a cached total that drifts from the list
 * under it is worse than no total at all.
 */
class MarketplaceStatistics
{
    /**
     * How far back the chart reads. A year is long enough to show a shape and
     * short enough that every bar still means something.
     */
    public const int MONTHS = 12;

    /**
     * What counts as recent, for the line under the installations tile.
     */
    public const int RECENT_DAYS = 30;

    /**
     * The shortest bar the chart draws, as a percentage. A month with nothing in
     * it still gets a sliver: a bar of no height reads as a hole in the chart
     * rather than as a quiet month.
     */
    private const int FLOOR = 4;

    /**
     * How many listings are public.
     */
    public function listingCount(): int
    {
        return $this->published()->count();
    }

    /**
     * What those listings are, since a catalogue of templates and one of
     * sections are two different places to have arrived at.
     */
    public function listingBreakdown(): string
    {
        $templates = $this->published()->where('listable_type', Template::class)->count();
        $sections = $this->published()->where('listable_type', Section::class)->count();

        return __(':templates, :sections', [
            'templates' => trans_choice(':count template|:count templates', $templates),
            'sections' => trans_choice(':count section|:count sections', $sections),
        ]);
    }

    /**
     * How many copies have been made out of the catalogue. Installations are
     * counted rather than summed off the listings, so this number and the chart
     * under it can never disagree.
     */
    public function installationCount(): int
    {
        return MarketplaceInstallation::query()->count();
    }

    public function recentInstallationLabel(): string
    {
        $count = MarketplaceInstallation::query()
            ->where('installed_at', '>=', CarbonImmutable::now()->subDays(self::RECENT_DAYS))
            ->count();

        return trans_choice('[0,*]:count in the last thirty days', $count);
    }

    /**
     * How many accounts have something in the catalogue.
     */
    public function publisherCount(): int
    {
        return $this->published()->distinct()->count('account_id');
    }

    public function recentPublisherLabel(): string
    {
        $count = $this->published()
            ->where('first_published_at', '>=', CarbonImmutable::now()->subWeek())
            ->distinct()
            ->count('account_id');

        return trans_choice(':count published something this week|:count published something this week', $count);
    }

    /**
     * How many languages the catalogue is written in, which is the one number
     * here that tells somebody browsing in their own language whether they will
     * find anything at all.
     */
    public function languageCount(): int
    {
        return $this->published()->distinct()->count('locale');
    }

    public function languageSummary(): string
    {
        $top = $this->published()
            ->pluck('locale')
            ->countBy()
            ->sortDesc()
            ->take(2)
            ->keys()
            ->map(fn (string $locale): string => config("monica.locales.{$locale}.label") ?? $locale);

        if ($top->isEmpty()) {
            return __('Nothing has been published yet');
        }

        if ($top->count() === 1) {
            return __('Most listings are in :language', ['language' => $top->first()]);
        }

        return __('Most listings are in :first or :second', [
            'first' => $top->first(),
            'second' => $top->last(),
        ]);
    }

    /**
     * The twelve bars, oldest first. Each carries the height it is drawn at
     * rather than the number it stands for, because a chart that leaves the
     * arithmetic to the template ends up doing it differently somewhere else.
     *
     * The tallest month is the full height and every other one is a share of it,
     * so the shape reads even in a year where the numbers are small.
     *
     * The rows are grouped here rather than in SQL because the expression that
     * cuts a timestamp into a month is written differently by every database we
     * support, and there is no version of this chart worth three dialects of it.
     *
     * @return array<int, array{label: string, count: int, height: int, isCurrent: bool}>
     */
    public function installationsPerMonth(): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $counts = MarketplaceInstallation::query()
            ->where('installed_at', '>=', $start)
            ->pluck('installed_at')
            ->countBy(fn (CarbonImmutable $moment): string => $moment->format('Y-m'));

        $tallest = max(1, (int) $counts->max());

        return new Collection(range(0, self::MONTHS - 1))
            ->map(function (int $offset) use ($start, $counts, $tallest): array {
                $month = $start->addMonths($offset);
                $count = (int) $counts->get($month->format('Y-m'), 0);

                return [
                    'label' => $month->isoFormat('MMM YYYY'),
                    'count' => $count,
                    'height' => max(self::FLOOR, (int) round($count / $tallest * 100)),
                    'isCurrent' => $month->isSameMonth(CarbonImmutable::now()),
                ];
            })
            ->all();
    }

    /**
     * What the chart says, in words, for whoever cannot read a picture.
     */
    public function currentMonthLabel(): string
    {
        $count = MarketplaceInstallation::query()
            ->where('installed_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();

        return trans_choice('[0,*]:count in :month', $count, [
            'month' => CarbonImmutable::now()->isoFormat('MMMM'),
        ]);
    }

    /**
     * @return Builder<MarketplaceListing>
     */
    private function published(): Builder
    {
        return MarketplaceListing::query()->where('status', MarketplaceListingStatusEnum::Published);
    }
}
