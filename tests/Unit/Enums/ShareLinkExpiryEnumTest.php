<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ShareLinkExpiryEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkExpiryEnumTest extends TestCase
{
    #[Test]
    public function it_counts_every_span_from_now(): void
    {
        $this->assertTrue(ShareLinkExpiryEnum::SevenDays->expiresAt()->isSameDay(now()->addDays(7)));
        $this->assertTrue(ShareLinkExpiryEnum::ThirtyDays->expiresAt()->isSameDay(now()->addDays(30)));
        $this->assertTrue(ShareLinkExpiryEnum::ThreeMonths->expiresAt()->isSameDay(now()->addMonths(3)));
    }

    #[Test]
    public function a_link_that_never_stops_has_no_date(): void
    {
        $this->assertNull(ShareLinkExpiryEnum::Never->expiresAt());
    }
}
