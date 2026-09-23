<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_many_visits(): void
    {
        $shareLink = ShareLink::factory()->create();
        ShareLinkVisit::factory()->count(3)->create(['share_link_id' => $shareLink->id]);

        $this->assertCount(3, $shareLink->visits);
    }

    #[Test]
    public function it_shows_nothing_sensitive_until_it_is_asked_to(): void
    {
        $shareLink = ShareLink::factory()->create();

        $this->assertFalse($shareLink->includes_files);
        $this->assertFalse($shareLink->includes_life_events);
        $this->assertFalse($shareLink->includes_life_event_narrative);
        $this->assertFalse($shareLink->includes_day_narrative);
        $this->assertFalse($shareLink->includes_relationships);
        $this->assertFalse($shareLink->includes_groups);
        $this->assertFalse($shareLink->includes_sensitive_fields);
    }

    #[Test]
    public function it_opens_while_it_is_neither_expired_nor_revoked(): void
    {
        $shareLink = ShareLink::factory()->create();

        $this->assertTrue($shareLink->isUsable());
    }

    #[Test]
    public function it_stops_opening_once_it_has_expired(): void
    {
        $shareLink = ShareLink::factory()->expired()->create();

        $this->assertFalse($shareLink->isUsable());
    }

    #[Test]
    public function it_stops_opening_once_it_is_revoked(): void
    {
        $shareLink = ShareLink::factory()->revoked()->create();

        $this->assertFalse($shareLink->isUsable());
    }

    #[Test]
    public function it_says_whether_a_password_stands_in_the_way(): void
    {
        $this->assertFalse(ShareLink::factory()->create()->isProtectedByAPassword());
        $this->assertTrue(ShareLink::factory()->withPassword()->create()->isProtectedByAPassword());
    }

    #[Test]
    public function it_is_addressed_by_its_uuid_and_never_by_its_token(): void
    {
        $shareLink = ShareLink::factory()->create();

        $this->assertEquals('uuid', $shareLink->getRouteKeyName());
        $this->assertNotNull($shareLink->uuid);
    }
}
