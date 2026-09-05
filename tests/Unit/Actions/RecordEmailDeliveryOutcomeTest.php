<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RecordEmailDeliveryOutcome;
use App\Enums\EmailDeliveryStatusEnum;
use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordEmailDeliveryOutcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['resend.webhook.secret' => 'whsec_'.base64_encode('scranton')]);
    }

    #[Test]
    public function it_records_that_the_email_arrived(): void
    {
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertInstanceOf(EmailSent::class, $recorded);
        $this->assertTrue($emailSent->is($recorded));
        $this->assertSame('2026-09-04 10:00:00', $recorded->delivered_at?->toDateTimeString());
        $this->assertNull($recorded->bounced_at);
        $this->assertSame(EmailDeliveryStatusEnum::Delivered, $recorded->fresh()?->delivery_status);
    }

    #[Test]
    public function it_records_that_the_email_came_back(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.bounced', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();

        $this->assertSame('2026-09-04 10:00:00', $recorded?->bounced_at?->toDateTimeString());
        $this->assertNull($recorded->delivered_at);
    }

    #[Test]
    public function it_records_that_the_recipient_marked_it_as_spam(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.complained', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Complained,
        )->execute();

        $this->assertSame('2026-09-04 10:00:00', $recorded?->complained_at?->toDateTimeString());
        $this->assertSame(EmailDeliveryStatusEnum::Complained, $recorded->fresh()?->delivery_status);
    }

    #[Test]
    public function it_takes_the_instant_from_the_report_and_not_from_the_email(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $payload = $this->report('email.delivered', 'a1b2c3');
        $payload['data']['created_at'] = '2020-01-01T00:00:00.000Z';

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $payload,
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertSame('2026-09-04 10:00:00', $recorded?->delivered_at?->toDateTimeString());
    }

    #[Test]
    public function it_leaves_an_instant_it_already_holds_alone(): void
    {
        EmailSent::factory()->create([
            'uuid' => 'a1b2c3',
            'delivered_at' => '2026-09-01 08:00:00',
        ]);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertSame('2026-09-01 08:00:00', $recorded?->delivered_at?->toDateTimeString());
    }

    #[Test]
    public function it_reads_as_bounced_whichever_order_a_bounce_and_a_delivery_arrive_in(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.bounced', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();

        $this->assertNotNull($recorded?->delivered_at);
        $this->assertNotNull($recorded->bounced_at);
        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $recorded->fresh()?->delivery_status);
    }

    #[Test]
    public function it_records_a_failed_send_as_a_bounce(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.failed', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();

        $this->assertSame('2026-09-04 10:00:00', $recorded?->bounced_at?->toDateTimeString());
    }

    #[Test]
    public function it_records_a_temporary_bounce_like_any_other_and_a_later_delivery_does_not_overturn_it(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $payload = $this->report('email.bounced', 'a1b2c3');
        $payload['data']['bounce'] = ['type' => 'Temporary', 'subType' => 'MailboxFull', 'message' => 'Full'];

        new RecordEmailDeliveryOutcome(
            payload: $payload,
            outcome: EmailDeliveryStatusEnum::Bounced,
        )->execute();

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $recorded?->fresh()?->delivery_status);
    }

    #[Test]
    public function it_records_nothing_while_no_signing_secret_is_configured(): void
    {
        config(['resend.webhook.secret' => null]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNull($recorded);
        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_treats_an_empty_signing_secret_as_no_secret_at_all(): void
    {
        config(['resend.webhook.secret' => '']);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_declines_a_report_that_names_no_email(): void
    {
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $payload = $this->report('email.delivered', 'a1b2c3');
        unset($payload['data']['email_id']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $payload,
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNull($recorded);
        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_declines_a_report_about_an_email_the_journal_does_not_hold(): void
    {
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.delivered', 'nobody-holds-this'),
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNull($recorded);
        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_declines_a_payload_it_cannot_read_at_all(): void
    {
        $recorded = new RecordEmailDeliveryOutcome(
            payload: ['nothing' => 'the provider documents'],
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNull($recorded);
    }

    #[Test]
    public function it_keeps_the_fact_when_the_instant_it_carries_is_unreadable(): void
    {
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $payload = $this->report('email.delivered', 'a1b2c3');
        $payload['created_at'] = 'the day Michael left Scranton';

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $payload,
            outcome: EmailDeliveryStatusEnum::Delivered,
        )->execute();

        $this->assertNotNull($recorded?->delivered_at);
        $this->assertSame(EmailDeliveryStatusEnum::Delivered, $recorded->fresh()?->delivery_status);
    }

    #[Test]
    public function it_records_nothing_for_an_outcome_no_report_can_announce(): void
    {
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $recorded = new RecordEmailDeliveryOutcome(
            payload: $this->report('email.sent', 'a1b2c3'),
            outcome: EmailDeliveryStatusEnum::Sent,
        )->execute();

        $this->assertNull($recorded);
        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function report(string $type, string $emailId): array
    {
        return [
            'type' => $type,
            'created_at' => '2026-09-04T10:00:00.000Z',
            'data' => [
                'created_at' => '2026-09-04T09:00:00.000Z',
                'email_id' => $emailId,
                'to' => ['michael.scott@dundermifflin.com'],
                'subject' => 'A sign-in from a new place',
            ],
        ];
    }
}
