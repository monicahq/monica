<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

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
}
