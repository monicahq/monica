<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ShareLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkTest extends TestCase
{
    use RefreshDatabase;

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
}
