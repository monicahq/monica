<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\MarketplaceListingVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_to_change_what_it_contains_once_it_is_approved(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->payload = ['type' => 'section', 'section' => ['slug' => 'something-else']];
        $version->save();
    }

    #[Test]
    public function it_can_still_be_withdrawn_once_it_is_approved(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $version->status = MarketplaceVersionStatusEnum::Withdrawn;
        $version->withdrawn_at = now();
        $version->save();

        $this->assertEquals(MarketplaceVersionStatusEnum::Withdrawn, $version->fresh()->status);
    }

    #[Test]
    public function it_lets_a_submitted_version_be_corrected(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $version->change_note = 'Renamed the field nobody understood.';
        $version->save();

        $this->assertEquals('Renamed the field nobody understood.', $version->fresh()->change_note);
    }
}
