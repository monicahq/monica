<?php

declare(strict_types=1);

namespace App\ViewModels\Marketplace;

use App\Enums\MarketplaceListingStatusEnum;
use App\Helpers\AvailableLocales;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\Template;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MarketplaceStatistics
{
    public const int MONTHS = 12;

    public const int RECENT_DAYS = 30;

    private const int FLOOR = 4;

    public function listingCount(): int
    {
        return $this->published()->count();
    }

    public function listingBreakdown(): string
    {
        $templates = $this->published()->where('listable_type', Template::class)->count();
        $sections = $this->published()->where('listable_type', Section::class)->count();

        return __(':templates, :sections', [
            'templates' => trans_choice(':count template|:count templates', $templates),
            'sections' => trans_choice(':count section|:count sections', $sections),
        ]);
    }

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
            ->map(fn (string $locale): string => (string) AvailableLocales::details($locale)['label']);

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

    /** @return array<int, array{label: string, count: int, height: int, isCurrent: bool}> */
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

    public function currentMonthLabel(): string
    {
        $count = MarketplaceInstallation::query()
            ->where('installed_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();

        return trans_choice('[0,*]:count in :month', $count, [
            'month' => CarbonImmutable::now()->isoFormat('MMMM'),
        ]);
    }

    /** @return Builder<MarketplaceListing> */
    private function published(): Builder
    {
        return MarketplaceListing::query()->where('status', MarketplaceListingStatusEnum::Published);
    }
}
