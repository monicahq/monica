<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Billing;

use App\Enums\AccessKindEnum;
use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\StripeOrder;
use App\Models\User;
use App\ViewModels\Settings\Account\Billing\BillingViewModel;
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
        $this->assertSame('2026-10-07', $viewModel->renewsAt()?->format('Y-m-d'));
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
    public function it_says_what_the_account_holds_against_its_ceilings(): void
    {
        config(['monica.free_vault_limit' => 1, 'monica.free_contact_limit' => 30]);

        $michael = User::factory()->create();
        $michael->account->update(['storage_used_in_bytes' => 5 * 1024 * 1024, 'storage_limit_in_bytes' => 1024 * 1024 * 1024]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertSame('0 of 1', $viewModel->vaultsHeld());
        $this->assertSame('0 of 30', $viewModel->contactsHeld());
        $this->assertSame('5 MB of 1 GB', $viewModel->storageHeld());
    }

    #[Test]
    public function it_says_there_is_no_ceiling_when_there_is_none(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now(), 'storage_limit_in_bytes' => null]);

        $viewModel = new BillingViewModel(user: $michael->fresh());

        $this->assertSame('0, no ceiling', $viewModel->vaultsHeld());
        $this->assertSame('0 B, no ceiling', $viewModel->storageHeld());
    }
}
