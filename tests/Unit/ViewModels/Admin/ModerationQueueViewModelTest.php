<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\PageSize;
use App\Models\MarketplaceListingVersion;
use App\ViewModels\Admin\ModerationQueueViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModerationQueueViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_what_has_waited_longest_first(): void
    {
        $newest = MarketplaceListingVersion::factory()->create(['created_at' => now()]);
        $oldest = MarketplaceListingVersion::factory()->create(['created_at' => now()->subWeek()]);

        $submissions = new ModerationQueueViewModel()->submissions();

        $this->assertEquals($oldest->id, $submissions->first()->id);
        $this->assertCount(2, $submissions);
        $this->assertEquals($newest->id, $submissions->last()->id);
    }

    #[Test]
    public function it_shows_only_what_nobody_has_decided_yet(): void
    {
        $waiting = MarketplaceListingVersion::factory()->create();
        MarketplaceListingVersion::factory()->approved()->create();
        MarketplaceListingVersion::factory()->rejected()->create();
        MarketplaceListingVersion::factory()->withdrawn()->create();

        $submissions = new ModerationQueueViewModel()->submissions();

        $this->assertCount(1, $submissions);
        $this->assertEquals($waiting->id, $submissions->first()->id);
    }

    #[Test]
    public function it_counts_what_is_waiting(): void
    {
        MarketplaceListingVersion::factory()->count(3)->create();
        MarketplaceListingVersion::factory()->approved()->create();

        $this->assertEquals(3, new ModerationQueueViewModel()->waitingCount());
    }

    #[Test]
    public function it_counts_nothing_on_an_instance_where_nothing_is_waiting(): void
    {
        $this->assertEquals(0, new ModerationQueueViewModel()->waitingCount());
    }

    #[Test]
    public function it_hands_back_one_page_at_a_time(): void
    {
        MarketplaceListingVersion::factory()->count(21)->create();

        $firstPage = new ModerationQueueViewModel()->submissions();

        $this->assertCount(20, $firstPage);
        $this->assertSame(21, $firstPage->total());

        $this->app['request']->merge(['page' => 2]);

        $this->assertCount(1, new ModerationQueueViewModel()->submissions());
    }

    #[Test]
    public function it_hands_back_everything_on_one_page_when_asked_for_all(): void
    {
        MarketplaceListingVersion::factory()->count(21)->create();

        $submissions = new ModerationQueueViewModel(size: PageSize::ALL)->submissions();

        $this->assertCount(21, $submissions);
        $this->assertSame(21, $submissions->total());
        $this->assertFalse($submissions->hasMorePages());
    }
}
