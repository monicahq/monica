<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\EmailDeliveryStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailDeliveryStatusEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_three_places_an_email_can_have_got_to(): void
    {
        $this->assertSame('bounced', EmailDeliveryStatusEnum::Bounced->value);
        $this->assertSame('delivered', EmailDeliveryStatusEnum::Delivered->value);
        $this->assertSame('sent', EmailDeliveryStatusEnum::Sent->value);
    }

    #[Test]
    public function it_has_no_fourth_state(): void
    {
        $this->assertCount(3, EmailDeliveryStatusEnum::cases());
    }
}
