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

    #[Test]
    public function it_warns_about_the_link_somebody_has_to_remember_to_revoke(): void
    {
        $this->assertStringContainsString('remember to revoke', ShareLinkExpiryEnum::Never->help());
        $this->assertStringContainsString('stops working on its own', ShareLinkExpiryEnum::ThirtyDays->help());
    }

    #[Test]
    public function every_case_carries_a_name_and_a_sentence_for_the_screen(): void
    {
        foreach (ShareLinkExpiryEnum::cases() as $case) {
            $this->assertNotEmpty($case->label());
            $this->assertSame([$case->label(), $case->help()], $case->translationKeys());
        }
    }
}
