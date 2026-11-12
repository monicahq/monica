<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Marketplace;

use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\ViewModels\Marketplace\MarketplaceStatistics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceStatisticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_is_published(): void
    {
        MarketplaceListing::factory()->count(2)->published()->create();
        MarketplaceListing::factory()->forSection()->published()->create();
        MarketplaceListing::factory()->create();

        $statistics = new MarketplaceStatistics;

        $this->assertSame(3, $statistics->listingCount());
        $this->assertSame('2 templates, 1 section', $statistics->listingBreakdown());
    }

    #[Test]
    public function it_counts_the_accounts_that_publish(): void
    {
        MarketplaceListing::factory()->count(3)->published()->create();

        $this->assertSame(3, new MarketplaceStatistics()->publisherCount());
    }

    #[Test]
    public function it_counts_the_languages_the_catalogue_is_written_in(): void
    {
        MarketplaceListing::factory()->count(2)->published()->create(['locale' => 'en']);
        MarketplaceListing::factory()->published()->create(['locale' => 'fr_FR']);

        $statistics = new MarketplaceStatistics;

        $this->assertSame(2, $statistics->languageCount());
        $this->assertSame('Most listings are in English or Français', $statistics->languageSummary());
    }

    #[Test]
    public function it_says_so_when_nothing_has_been_published(): void
    {
        $statistics = new MarketplaceStatistics;

        $this->assertSame(0, $statistics->listingCount());
        $this->assertSame('Nothing has been published yet', $statistics->languageSummary());
    }

    #[Test]
    public function it_counts_the_installations(): void
    {
        MarketplaceInstallation::factory()->count(2)->create();

        $this->assertSame(2, new MarketplaceStatistics()->installationCount());
    }

    #[Test]
    public function it_spreads_the_installations_over_twelve_months(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2026-08-12'));

        MarketplaceInstallation::factory()->create(['installed_at' => CarbonImmutable::parse('2026-08-02')]);
        MarketplaceInstallation::factory()->create(['installed_at' => CarbonImmutable::parse('2026-07-14')]);
        MarketplaceInstallation::factory()->create(['installed_at' => CarbonImmutable::parse('2024-01-01')]);

        $months = new MarketplaceStatistics()->installationsPerMonth();

        $this->assertCount(12, $months);
        $this->assertSame('Sep 2025', $months[0]['label']);
        $this->assertSame('Aug 2026', $months[11]['label']);
        $this->assertSame(1, $months[11]['count']);
        $this->assertTrue($months[11]['isCurrent']);
        $this->assertSame(1, $months[10]['count']);
        $this->assertSame(0, $months[0]['count']);
    }
}
