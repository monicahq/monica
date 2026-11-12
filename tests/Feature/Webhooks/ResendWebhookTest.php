<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Enums\EmailDeliveryStatusEnum;
use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Resend\SignedWebhookRequest;
use Tests\TestCase;

class ResendWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const string SECRET = 'whsec_c2NyYW50b24=';

    #[Test]
    public function it_records_a_delivery_reported_at_the_provider_endpoint(): void
    {
        config(['resend.webhook.secret' => self::SECRET]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $this->report('email.delivered', 'a1b2c3')->assertOk();

        $this->assertSame(
            '2026-09-04 10:00:00',
            $emailSent->fresh()?->delivered_at?->toDateTimeString(),
        );
        $this->assertSame(EmailDeliveryStatusEnum::Delivered, $emailSent->fresh()?->delivery_status);
    }

    #[Test]
    public function it_records_a_complaint_reported_at_the_provider_endpoint(): void
    {
        config(['resend.webhook.secret' => self::SECRET]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $this->report('email.complained', 'a1b2c3')->assertOk();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->fresh()?->delivery_status);
    }

    #[Test]
    public function it_refuses_a_report_whose_signature_does_not_match(): void
    {
        config(['resend.webhook.secret' => self::SECRET]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $request = $this->signedReport('email.delivered', 'a1b2c3');

        $this->send($request->headersSignedByAnImpostor(), $request->body())
            ->assertForbidden();

        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_records_nothing_when_no_secret_is_configured_whatever_the_endpoint_answers(): void
    {
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $request = $this->signedReport('email.delivered', 'a1b2c3');

        config(['resend.webhook.secret' => null]);

        $this->send($request->headers(), $request->body());

        $this->assertNull($emailSent->fresh()?->delivered_at);
    }

    #[Test]
    public function it_leaves_every_row_alone_for_a_report_it_does_not_act_on(): void
    {
        config(['resend.webhook.secret' => self::SECRET]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        $this->report('email.opened', 'a1b2c3')->assertSuccessful();

        $emailSent->refresh();

        $this->assertNull($emailSent->delivered_at);
        $this->assertNull($emailSent->bounced_at);
        $this->assertNull($emailSent->complained_at);
        $this->assertSame(EmailDeliveryStatusEnum::Sent, $emailSent->delivery_status);
    }

    #[Test]
    public function it_never_answers_with_a_server_error_for_a_report_it_cannot_use(): void
    {
        config(['resend.webhook.secret' => self::SECRET]);
        $emailSent = EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        foreach (['email.delivered', 'email.bounced', 'email.complained', 'email.clicked'] as $type) {
            $this->report($type, 'nobody-holds-this')->assertSuccessful();
        }

        $emailSent->refresh();

        $this->assertNull($emailSent->delivered_at);
        $this->assertNull($emailSent->bounced_at);
        $this->assertNull($emailSent->complained_at);
    }

    /** @param  array<string, mixed>  $extraData */
    private function report(string $type, string $emailId, array $extraData = []): TestResponse
    {
        $request = $this->signedReport($type, $emailId, $extraData);

        return $this->send($request->headers(), $request->body());
    }

    /** @param  array<string, mixed>  $extraData */
    private function signedReport(string $type, string $emailId, array $extraData = []): SignedWebhookRequest
    {
        return new SignedWebhookRequest(
            payload: [
                'type' => $type,
                'created_at' => '2026-09-04T10:00:00.000Z',
                'data' => [
                    'created_at' => '2026-09-04T09:00:00.000Z',
                    'email_id' => $emailId,
                    ...$extraData,
                ],
            ],
            secret: self::SECRET,
        );
    }

    /** @param  array<string, string>  $headers */
    private function send(array $headers, string $body): TestResponse
    {
        return $this->call(
            'POST',
            'resend/webhook',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([...$headers, 'CONTENT_TYPE' => 'application/json']),
            $body,
        );
    }
}
