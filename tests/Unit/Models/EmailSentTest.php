<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EmailDeliveryStatusEnum;
use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_as_sent_while_nothing_has_come_back(): void
    {
        $emailSent = EmailSent::factory()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Sent, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_delivered_once_it_arrived(): void
    {
        $emailSent = EmailSent::factory()->delivered()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Delivered, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_bounced_once_it_came_back(): void
    {
        $emailSent = EmailSent::factory()->bounced()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_bounced_when_the_provider_reported_both(): void
    {
        $emailSent = EmailSent::factory()->delivered()->bounced()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_complained_even_when_it_also_bounced(): void
    {
        $emailSent = EmailSent::factory()->bounced()->complained()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_complained_even_when_it_was_delivered_first(): void
    {
        $emailSent = EmailSent::factory()->delivered()->complained()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->delivery_status);
    }
}
