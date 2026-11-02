<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Billing;

use App\Enums\AccessKindEnum;
use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\ViewModels\Settings\Account\Billing\BillingViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingViewModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_prices_every_offer_in_the_currency_it_shows(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);
        $this->app->setLocale('en');

        $offers = new BillingViewModel(user: $michael, currency: 'usd')->offers();

        $this->assertCount(3, $offers);
        $this->assertSame(OfferKindEnum::Monthly, $offers[0]['kind']);
        $this->assertSame('price_monthly', $offers[0]['price_id']);
        $this->assertSame('$10.00/month', $offers[0]['price']);
        $this->assertSame('$100.00/year', $offers[1]['price']);
        $this->assertSame('$219.00 once', $offers[2]['price']);
        $this->assertFalse($offers[0]['upgrade']);
    }

    #[Test]
    public function it_falls_back_to_the_price_stripe_quotes_when_the_offer_is_not_priced_in_the_chosen_currency(): void
    {
        $prices = $this->stripePrices();
        $prices['data'][0]['currency_options'] = ['eur' => ['unit_amount' => 900]];
        $this->fakeStripe(['GET prices' => $prices]);
        StripeCatalogue::forget();

        $michael = User::factory()->create();
        $this->app->setLocale('en');

        $offers = new BillingViewModel(user: $michael, currency: 'usd')->offers();

        $this->assertSame('€9.00/month', $offers[0]['price']);
        $this->assertSame('$100.00/year', $offers[1]['price']);
    }

    #[Test]
    public function it_shows_the_currency_the_reader_picked(): void
    {
        $michael = User::factory()->create();

        $this->assertSame('usd', new BillingViewModel(user: $michael, currency: 'usd')->currency());
    }

    #[Test]
    public function it_suggests_the_currency_of_the_language_when_nothing_was_picked(): void
    {
        config(['localization.locales.en.currency' => 'usd']);

        $michael = User::factory()->create();
        $this->app->setLocale('en');

        $this->assertSame('usd', new BillingViewModel(user: $michael)->currency());
    }

    #[Test]
    public function it_never_shows_a_currency_the_catalogue_does_not_quote(): void
    {
        config(['localization.locales.en.currency' => 'gbp']);

        $michael = User::factory()->create();
        $this->app->setLocale('en');

        $this->assertSame('eur', new BillingViewModel(user: $michael, currency: 'chf')->currency());
        $this->assertSame(['eur', 'usd'], new BillingViewModel(user: $michael)->currencies());
    }

    #[Test]
    public function it_offers_nothing_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $michael = User::factory()->create();
        $viewModel = new BillingViewModel(user: $michael);

        $this->assertTrue($viewModel->isSelfHosted());
        $this->assertSame([], $viewModel->offers());
    }

    #[Test]
    public function it_offers_nothing_to_a_lifetime_holder(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertSame([], $viewModel->offers());
        $this->assertTrue($viewModel->holdsLifetimeAccess());
        $this->assertSame(AccessKindEnum::Lifetime, $viewModel->accessKind());
        $this->assertSame('Lifetime access', $viewModel->accessKindLabel());
        $this->assertSame($order->id, $viewModel->order()?->id);
        $this->assertSame($order->created_at->toDateString(), $viewModel->purchasedAt()?->toDateString());
    }

    #[Test]
    public function it_speaks_of_no_order_when_nobody_paid_one(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account_id]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertNull($viewModel->order());
        $this->assertNull($viewModel->purchasedAt());
        $this->assertFalse($viewModel->holdsLifetimeAccess());
    }

    #[Test]
    public function it_keeps_speaking_of_an_order_that_stopped_holding(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertSame($order->id, $viewModel->order()?->id);
        $this->assertFalse($viewModel->hasFullAccess());
    }

    #[Test]
    public function it_says_whether_stripe_knows_the_account(): void
    {
        $michael = User::factory()->create();

        $this->assertFalse(new BillingViewModel(user: $michael)->isKnownToStripe());

        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $this->assertTrue(new BillingViewModel(user: $michael->fresh())->isKnownToStripe());
    }

    #[Test]
    public function it_says_when_access_ended_and_why(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertSame($order->updated_at?->toDateString(), $viewModel->accessEndedAt()?->toDateString());
        $this->assertStringContainsString('refunded', (string) $viewModel->accessEndedReason());
    }

    #[Test]
    public function it_names_a_chargeback_rather_than_a_refund(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->disputed()->create(['account_id' => $michael->account_id]);

        $this->assertStringContainsString('charged back', (string) new BillingViewModel(user: $michael->fresh())->accessEndedReason());
    }

    #[Test]
    public function it_says_nothing_about_an_end_while_access_stands(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertNull($viewModel->accessEndedAt());
        $this->assertNull($viewModel->accessEndedReason());
    }

    #[Test]
    public function it_shows_the_grant_as_access_held_for_good(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertTrue($viewModel->holdsLifetimeAccess());
        $this->assertTrue($viewModel->wasGranted());
        $this->assertNull($viewModel->purchasedAt());
    }

    #[Test]
    public function it_marks_a_longer_subscription_as_a_switch_rather_than_a_purchase(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        $offers = new BillingViewModel(user: $michael->fresh())->offers();

        $this->assertSame(['price_yearly', 'price_lifetime'], array_column($offers, 'price_id'));
        $this->assertTrue($offers[0]['upgrade']);
        $this->assertFalse($offers[1]['upgrade']);
    }

    #[Test]
    public function it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe(): void
    {
        $michael = User::factory()->create();

        $this->assertTrue(new BillingViewModel(user: $michael, returnedFromCheckout: true)->isAwaitingConfirmation());
        $this->assertFalse(new BillingViewModel(user: $michael)->isAwaitingConfirmation());
    }

    #[Test]
    public function it_stops_saying_so_once_stripe_has_confirmed(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        $this->assertFalse(new BillingViewModel(user: $michael->fresh(), returnedFromCheckout: true)->isAwaitingConfirmation());
    }

    #[Test]
    public function it_says_the_offers_could_not_be_read(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('prices*');
        StripeCatalogue::forget();

        $michael = User::factory()->create();

        $this->assertFalse(new BillingViewModel(user: $michael)->offersAvailable());
    }

    #[Test]
    public function it_names_the_subscription_a_subscriber_is_on(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_yearly');

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertTrue($viewModel->isSubscribed());
        $this->assertSame(AccessKindEnum::Subscription, $viewModel->accessKind());
        $this->assertSame('Yearly subscription', $viewModel->accessKindLabel());
        $this->assertSame($this->stripePeriodEnd()->format('Y-m-d'), $viewModel->renewsAt()?->format('Y-m-d'));
        $this->assertFalse($viewModel->isEnding());
    }

    #[Test]
    public function it_says_when_a_cancelled_subscription_ends(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertTrue($viewModel->isEnding());
        $this->assertSame(now()->addWeek()->format('Y-m-d'), $viewModel->endsAt()?->format('Y-m-d'));
        $this->assertNull($viewModel->renewsAt());
    }

    #[Test]
    public function it_says_access_was_granted_rather_than_bought(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertTrue($viewModel->wasGranted());
        $this->assertSame('Granted by us', $viewModel->accessKindLabel());
        $this->assertSame(0, $viewModel->paymentCount());
    }

    #[Test]
    public function it_measures_what_the_account_holds_against_the_ceilings_of_the_free_plan(): void
    {
        config(['monica.free_vault_limit' => 1, 'monica.free_contact_limit' => 30]);

        $michael = User::factory()->create();
        $michael->account->update(['storage_used_in_bytes' => 512 * 1024 * 1024, 'storage_limit_in_bytes' => 1024 * 1024 * 1024]);

        $usage = new BillingViewModel(user: $michael->fresh())->usage();

        $this->assertSame(['Contacts', 'Vaults', 'Storage'], array_column($usage, 'label'));
        $this->assertSame('0 of 30', $usage[0]['value']);
        $this->assertSame('Free plan limit: 30', $usage[0]['note']);
        $this->assertSame(0, $usage[0]['percent']);
        $this->assertSame('512 MB of 1 GB', $usage[2]['value']);
        $this->assertSame('Free plan limit: 1 GB', $usage[2]['note']);
        $this->assertSame(50, $usage[2]['percent']);
    }

    #[Test]
    public function it_draws_no_bar_where_there_is_no_ceiling(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $usage = new BillingViewModel(user: $michael->fresh())->usage();

        $this->assertSame('0', $usage[1]['value']);
        $this->assertSame('Unlimited', $usage[1]['note']);
        $this->assertNull($usage[1]['percent']);
        $this->assertSame('Files and photos', $usage[2]['note']);
    }

    #[Test]
    public function it_prices_the_amount_alone_beside_the_whole_price(): void
    {
        $michael = User::factory()->create();
        $this->app->setLocale('en');

        $offers = new BillingViewModel(user: $michael, currency: 'usd')->offers();

        $this->assertSame('$10.00', $offers[0]['amount']);
        $this->assertSame('$219.00', $offers[2]['amount']);
    }

    #[Test]
    public function it_says_what_twelve_months_cost_against_one_year(): void
    {
        $michael = User::factory()->create();
        $this->app->setLocale('en');

        $this->assertSame('$20.00', new BillingViewModel(user: $michael, currency: 'usd')->yearlySaving());
    }

    #[Test]
    public function it_names_no_saving_when_yearly_saves_nothing(): void
    {
        $prices = $this->stripePrices();
        $prices['data'][1]['currency_options']['usd'] = ['unit_amount' => 12000];
        $this->fakeStripe(['GET prices' => $prices]);
        StripeCatalogue::forget();

        $michael = User::factory()->create();

        $this->assertNull(new BillingViewModel(user: $michael, currency: 'usd')->yearlySaving());
    }

    #[Test]
    public function it_names_no_saving_when_there_is_no_monthly_price(): void
    {
        $this->fakeStripe(kinds: ['yearly', 'lifetime']);
        StripeCatalogue::forget();

        $michael = User::factory()->create();

        $this->assertNull(new BillingViewModel(user: $michael, currency: 'usd')->yearlySaving());
    }

    #[Test]
    public function it_lists_the_three_newest_payments_with_what_each_bought(): void
    {
        $michael = User::factory()->create();
        $this->app->setLocale('en');

        foreach ([4, 3, 2, 1] as $monthsAgo) {
            Payment::factory()->create([
                'account_id' => $michael->account_id,
                'kind' => OfferKindEnum::Monthly,
                'total' => 1000,
                'billed_at' => now()->subMonths($monthsAgo),
            ]);
        }

        $payments = new BillingViewModel(user: $michael)->recentPayments();

        $this->assertCount(3, $payments);
        $this->assertEqualsWithDelta(now()->subMonth()->timestamp, $payments[0]['billed_at']->timestamp, 5);
        $this->assertSame('Monthly subscription', $payments[0]['label']);
        $this->assertSame('€10.00', $payments[0]['total']);
    }

    #[Test]
    public function it_says_nothing_at_the_top_to_a_free_account_that_never_paid(): void
    {
        $michael = User::factory()->create();

        $this->assertNull(new BillingViewModel(user: $michael)->summary());
    }

    #[Test]
    public function it_sums_up_a_running_subscription(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, priceId: 'price_yearly');
        $this->app->setLocale('en');

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Active', $summary['badge']);
        $this->assertSame('Yearly subscription', $summary['title']);
    }

    #[Test]
    public function it_sums_up_a_subscription_moving_to_yearly_with_the_day_it_does(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, priceId: 'price_monthly')->forceFill(['scheduled_stripe_price' => 'price_yearly'])->save();
        $this->app->setLocale('en');

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Active', $summary['badge']);
        $this->assertSame('Monthly subscription', $summary['title']);
        $this->assertSame("Full access. Yearly subscription from {$this->stripePeriodEnd()->isoFormat('LL')}, the day its price is first billed.", $summary['line']);
    }

    #[Test]
    public function it_sums_up_a_subscription_that_is_ending_with_its_last_day(): void
    {
        $michael = User::factory()->create();
        $endsAt = CarbonImmutable::parse('2027-03-12');
        $this->travelTo($endsAt->subMonth());
        $this->subscribeAccount($michael->account, priceId: 'price_yearly', endsAt: $endsAt);
        $this->app->setLocale('en');

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Ending', $summary['badge']);
        $this->assertStringContainsString('March 12, 2027', $summary['line']);
    }

    #[Test]
    public function it_sums_up_lifetime_access_with_the_day_it_was_paid(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id, 'created_at' => '2026-05-03 10:00:00']);
        $this->app->setLocale('en');

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Lifetime', $summary['badge']);
        $this->assertSame('Paid once on May 3, 2026. Full access for the lifetime of the service, with no renewals.', $summary['line']);
    }

    #[Test]
    public function it_sums_up_a_subscription_that_ended_as_the_free_plan(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, priceId: 'price_yearly', status: 'canceled', endsAt: CarbonImmutable::parse('2026-03-12'));
        Payment::factory()->create(['account_id' => $michael->account_id, 'kind' => OfferKindEnum::Yearly]);
        $this->app->setLocale('en');

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Ended', $summary['badge']);
        $this->assertSame('Free plan', $summary['title']);
        $this->assertStringContainsString('March 12, 2026', $summary['line']);
    }

    #[Test]
    public function it_sums_up_access_that_was_granted_without_a_payment(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $summary = new BillingViewModel(user: $michael->fresh())->summary();

        $this->assertSame('Granted', $summary['badge']);
        $this->assertSame('Full access', $summary['title']);
    }
}
