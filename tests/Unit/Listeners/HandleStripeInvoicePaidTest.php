<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\AccessKindEnum;
use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeInvoicePaid;
use App\Models\Account;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandleStripeInvoicePaidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
        Queue::fake();
        Notification::fake();
    }

    #[Test]
    public function it_records_a_purchase_invoice_without_opening_anything(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $invoice = $this->paidInvoicePayload();

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($invoice)));

        $this->assertDatabaseHas('payments', ['account_id' => $account->id, 'stripe_id' => $invoice['id']]);
        $this->assertFalse($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_records_a_renewal_without_touching_access(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $this->subscribeAccount($account);

        $invoice = $this->paidInvoicePayload(subscriptionId: 'sub_dundermifflin');

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($invoice)));

        $this->assertDatabaseHas('payments', ['stripe_id' => $invoice['id'], 'stripe_subscription_id' => 'sub_dundermifflin']);
        $this->assertSame(AccessKindEnum::Subscription, $account->fresh()->accessKind());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_leaves_one_payment_when_stripe_repeats_itself(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $invoice = $this->paidInvoicePayload();

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($invoice)));
        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($invoice)));

        $this->assertSame(1, Payment::query()->count());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_a_confirmation_naming_a_customer_it_does_not_know(): void
    {
        Log::spy();

        Account::factory()->create();

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($this->paidInvoicePayload('cus_nobody'))));

        $this->assertSame(0, Payment::query()->count());
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_never_matches_an_account_stripe_does_not_know_yet(): void
    {
        $account = Account::factory()->create();

        $invoice = $this->paidInvoicePayload();
        $invoice['customer'] = null;

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($this->event($invoice)));

        $this->assertSame(0, Payment::query()->count());
        $this->assertFalse($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_ignores_every_other_event(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $payload = $this->event($this->paidInvoicePayload());
        $payload['type'] = 'invoice.payment_failed';

        new HandleStripeInvoicePaid()->handle(new WebhookReceived($payload));

        $this->assertSame(0, Payment::query()->count());
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeInvoicePaid;

        $this->assertSame(5, $listener->tries);
        $this->assertNotEmpty($listener->backoff);
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
}
