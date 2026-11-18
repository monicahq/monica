<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\OfferKindEnum;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\StripeOrder;
use App\ViewModels\Admin\BillingFigures;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingFiguresTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_access_in_the_order_account_access_kind_uses(): void
    {
        $michael = Account::factory()->create(['access_granted_at' => now()]);
        StripeOrder::factory()->settled()->create(['account_id' => $michael->id]);
        Account::factory()->create(['access_granted_at' => now()]);
        $jim = Account::factory()->create();
        $this->subscribe($jim, 'sub_jim');
        Payment::factory()->create(['account_id' => $jim->id, 'stripe_subscription_id' => 'sub_jim', 'kind' => OfferKindEnum::Monthly]);
        Account::factory()->create();

        $access = new BillingFigures()->access();

        $this->assertSame(['lifetime' => 1, 'granted' => 1, 'monthly' => 1, 'yearly' => 0, 'kindNotKnown' => 0, 'none' => 1], $access);
    }

    #[Test]
    public function it_counts_subscribers_by_the_kind_of_their_latest_payment(): void
    {
        $pam = Account::factory()->create();
        $this->subscribe($pam, 'sub_pam');
        Payment::factory()->create(['account_id' => $pam->id, 'stripe_subscription_id' => 'sub_pam', 'kind' => OfferKindEnum::Monthly, 'billed_at' => now()->subMonths(2)]);
        Payment::factory()->create(['account_id' => $pam->id, 'stripe_subscription_id' => 'sub_pam', 'kind' => OfferKindEnum::Yearly, 'billed_at' => now()->subDay()]);

        $access = new BillingFigures()->access();

        $this->assertSame(0, $access['monthly']);
        $this->assertSame(1, $access['yearly']);
    }

    #[Test]
    public function it_counts_a_subscription_with_no_payment_as_kind_not_known(): void
    {
        $this->subscribe(Account::factory()->create(), 'sub_ryan');

        $this->assertSame(1, new BillingFigures()->access()['kindNotKnown']);
    }

    #[Test]
    public function it_counts_the_accounts_holding_no_full_access(): void
    {
        Account::factory()->count(2)->create();
        $this->subscribe(Account::factory()->create(), 'sub_toby', status: 'canceled', endsAt: now()->subDay()->toImmutable());

        $this->assertSame(3, new BillingFigures()->access()['none']);
    }

    #[Test]
    public function it_counts_active_cancelled_but_running_and_past_due_subscriptions(): void
    {
        $this->subscribe(Account::factory()->create(), 'sub_angela');
        $this->subscribe(Account::factory()->create(), 'sub_oscar', endsAt: now()->addWeek()->toImmutable());
        $this->subscribe(Account::factory()->create(), 'sub_kevin', status: 'past_due');
        $this->subscribe(Account::factory()->create(), 'sub_creed', status: 'canceled', endsAt: now()->subDay()->toImmutable());

        $this->assertSame(['active' => 1, 'cancelledButRunning' => 1, 'pastDue' => 1], new BillingFigures()->subscriptions());
    }

    #[Test]
    public function it_totals_this_month_and_last_month_per_currency_and_kind(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        Payment::factory()->create(['kind' => OfferKindEnum::Monthly, 'currency' => 'eur', 'total' => 900, 'tax' => 150, 'billed_at' => now()->subDay()]);
        Payment::factory()->create(['kind' => OfferKindEnum::Monthly, 'currency' => 'eur', 'total' => 900, 'tax' => 150, 'billed_at' => now()->subDays(5)]);
        Payment::factory()->create(['kind' => OfferKindEnum::Lifetime, 'currency' => 'usd', 'total' => 19900, 'tax' => 0, 'billed_at' => now()->subDays(3)]);
        Payment::factory()->create(['kind' => OfferKindEnum::Yearly, 'currency' => 'eur', 'total' => 9000, 'tax' => 1500, 'billed_at' => '2026-10-31 23:00:00']);

        $months = new BillingFigures()->payments();

        $this->assertEquals(CarbonImmutable::parse('2026-11-01'), $months[0]['month']);
        $this->assertSame([
            ['kind' => OfferKindEnum::Monthly, 'currency' => 'eur', 'count' => 2, 'total' => 1800, 'tax' => 300],
            ['kind' => OfferKindEnum::Lifetime, 'currency' => 'usd', 'count' => 1, 'total' => 19900, 'tax' => 0],
        ], $months[0]['rows']);
        $this->assertSame([
            ['kind' => OfferKindEnum::Yearly, 'currency' => 'eur', 'count' => 1, 'total' => 9000, 'tax' => 1500],
        ], $months[1]['rows']);
    }

    #[Test]
    public function it_leaves_out_payments_older_than_last_month(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        Payment::factory()->create(['billed_at' => '2026-09-30 12:00:00']);

        $months = new BillingFigures()->payments();

        $this->assertSame([], $months[0]['rows']);
        $this->assertSame([], $months[1]['rows']);
    }

    #[Test]
    public function it_counts_full_refunds_and_disputes_of_the_last_thirty_days(): void
    {
        StripeEvent::factory()->create(['type' => 'charge.refunded', 'created_at' => now()->subDays(3)]);
        StripeEvent::factory()->create(['type' => 'charge.refunded', 'created_at' => now()->subDays(31)]);
        StripeEvent::factory()->create(['type' => 'charge.dispute.created', 'created_at' => now()->subDays(10)]);

        $figures = new BillingFigures;

        $this->assertSame(1, $figures->refunds());
        $this->assertSame(1, $figures->disputes());
    }

    #[Test]
    public function it_says_no_payment_was_ever_recorded(): void
    {
        $this->assertFalse(new BillingFigures()->hasAnyPayment());

        Payment::factory()->create();

        $this->assertTrue(new BillingFigures()->hasAnyPayment());
    }

    private function subscribe(Account $account, string $stripeId, string $status = 'active', ?CarbonImmutable $endsAt = null): void
    {
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => $status,
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
            'ends_at' => $endsAt,
        ]);
    }
}
