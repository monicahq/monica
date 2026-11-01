<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Console\WebhookCommand;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\SignedWebhookRequest;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const string SECRET = 'whsec_dundermifflin';

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
        config(['cashier.webhook.secret' => self::SECRET]);
        Notification::fake();
    }

    #[Test]
    public function it_opens_access_on_a_completed_session_stripe_signed(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        StripeOrder::factory()->create(['account_id' => $michael->account_id, 'stripe_id' => 'cs_test_scranton']);

        $request = new SignedWebhookRequest(payload: $this->sessionEvent(), secret: self::SECRET);

        $response = $this->call('POST', 'stripe/webhook', [], [], [], $this->server($request->headers()), $request->body());

        $response->dump();
        $response->assertOk();
        $this->assertDatabaseHas('stripe_orders', ['stripe_id' => 'cs_test_scranton', 'stripe_status' => StripeOrder::PAID]);
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_records_a_payment_stripe_signed(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $invoice = $this->paidInvoicePayload();
        $request = new SignedWebhookRequest(payload: $this->event($invoice), secret: self::SECRET);

        $response = $this->call('POST', 'stripe/webhook', [], [], [], $this->server($request->headers()), $request->body());

        $response->assertOk();
        $this->assertDatabaseHas('payments', ['account_id' => $michael->account_id, 'stripe_id' => $invoice['id']]);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_payment_nobody_signed(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $request = new SignedWebhookRequest(payload: $this->event($this->paidInvoicePayload()), secret: self::SECRET);

        $response = $this->call('POST', 'stripe/webhook', [], [], [], $this->server($request->headersWithNoSignature()), $request->body());

        $response->assertForbidden();
        $this->assertSame(0, Payment::query()->count());
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_payment_signed_by_somebody_else(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $request = new SignedWebhookRequest(payload: $this->event($this->paidInvoicePayload()), secret: self::SECRET);

        $response = $this->call('POST', 'stripe/webhook', [], [], [], $this->server($request->headersSignedByAnImpostor()), $request->body());

        $response->assertForbidden();
        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function it_needs_no_session_token_of_ours(): void
    {
        $request = new SignedWebhookRequest(
            payload: ['id' => 'evt_dundermifflin', 'type' => 'invoice.finalized', 'data' => ['object' => []]],
            secret: self::SECRET,
        );

        $this->call('POST', 'stripe/webhook', [], [], [], $this->server($request->headers()), $request->body())
            ->assertOk();
    }

    #[Test]
    public function every_event_a_listener_acts_on_is_one_stripe_was_asked_to_send(): void
    {
        $configured = config('cashier.webhook.events');
        $missing = [];

        foreach (glob(app_path('Listeners/HandleStripe*.php')) as $listener) {
            preg_match_all(
                "/'(checkout|customer|invoice|charge|payment_method)\.[a-z_.]+'/",
                (string) file_get_contents($listener),
                $matches,
            );

            foreach (array_unique($matches[0]) as $quoted) {
                $event = trim($quoted, "'");

                if (! in_array($event, $configured, true)) {
                    $missing[] = basename($listener).' acts on '.$event;
                }
            }
        }

        $this->assertSame([], $missing, "Stripe is never asked to send these, so the listener never runs:\n".implode("\n", $missing));
    }

    #[Test]
    public function it_asks_stripe_for_more_than_the_package_would_on_its_own(): void
    {
        $configured = config('cashier.webhook.events');

        foreach (WebhookCommand::DEFAULT_EVENTS as $event) {
            $this->assertContains($event, $configured);
        }

        foreach (['invoice.paid', 'charge.refunded', 'charge.refund.updated', 'charge.dispute.created', 'charge.dispute.closed'] as $event) {
            $this->assertContains($event, $configured);
            $this->assertNotContains($event, WebhookCommand::DEFAULT_EVENTS);
        }
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array<string, mixed>
     */
    private function event(array $invoice): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'invoice.paid',
            'data' => ['object' => $invoice],
        ];
    }

    /** @return array<string, mixed> */
    private function sessionEvent(): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid')],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function server(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($name))] = $value;
        }

        return $server;
    }
}
