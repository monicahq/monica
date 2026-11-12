<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
    }

    #[Test]
    public function it_offers_every_tagged_price_from_the_shortest_commitment_to_the_longest(): void
    {
        $this->fakeStripe();

        $offers = StripeCatalogue::offers();

        $this->assertCount(3, $offers);
        $this->assertSame(['price_monthly', 'price_yearly', 'price_lifetime'], array_column($offers, 'price_id'));
        $this->assertSame([OfferKindEnum::Monthly, OfferKindEnum::Yearly, OfferKindEnum::Lifetime], array_column($offers, 'kind'));
        $this->assertSame(900, $offers[0]['amount']);
        $this->assertSame('eur', $offers[0]['currency']);
        $this->assertSame(['eur' => 900, 'usd' => 1000], $offers[0]['prices']);
    }

    #[Test]
    public function it_asks_stripe_for_the_active_prices_with_their_currencies_and_their_products(): void
    {
        $client = $this->fakeStripe();

        StripeCatalogue::offers();

        $params = $client->lastParams('GET prices');
        $this->assertSame('true', $params['active']);
        $this->assertSame(100, $params['limit']);
        $this->assertSame(['data.currency_options', 'data.product'], $params['expand']);
    }

    #[Test]
    public function it_sells_every_product_when_the_configuration_names_no_metadata_key(): void
    {
        $this->fakeStripe();

        config(['monica.product_tag.key' => null, 'monica.product_tag.value' => 'monica']);

        $this->assertCount(3, StripeCatalogue::offers());
    }

    #[Test]
    public function it_only_sells_a_product_whose_metadata_holds_the_value_the_configuration_names(): void
    {
        Log::spy();

        $prices = $this->stripePrices();
        $prices['data'][0]['product']['metadata'] = ['application' => 'monica'];
        $prices['data'][1]['product']['metadata'] = ['application' => 'dundermifflin'];

        $this->fakeStripe(['GET prices' => $prices]);

        config(['monica.product_tag.key' => 'application', 'monica.product_tag.value' => 'monica']);

        $this->assertSame(['price_monthly'], array_column(StripeCatalogue::offers(), 'price_id'));
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_only_asks_that_the_metadata_key_is_there_when_the_configuration_names_no_value(): void
    {
        $prices = $this->stripePrices();
        $prices['data'][0]['product']['metadata'] = ['application' => 'whatever'];

        $this->fakeStripe(['GET prices' => $prices]);

        config(['monica.product_tag.key' => 'application', 'monica.product_tag.value' => null]);

        $this->assertSame(['price_monthly'], array_column(StripeCatalogue::offers(), 'price_id'));
    }

    #[Test]
    public function it_leaves_out_a_price_whose_product_stripe_did_not_send(): void
    {
        $prices = $this->stripePrices();
        $prices['data'][0]['product']['metadata'] = ['application' => 'monica'];
        $prices['data'][1]['product'] = 'prod_full_access';

        $this->fakeStripe(['GET prices' => $prices]);

        config(['monica.product_tag.key' => 'application', 'monica.product_tag.value' => 'monica']);

        $this->assertSame(['price_monthly'], array_column(StripeCatalogue::offers(), 'price_id'));
    }

    #[Test]
    public function it_lists_every_currency_the_catalogue_quotes(): void
    {
        $this->fakeStripe();

        $this->assertSame(['eur', 'usd'], StripeCatalogue::currencies());
    }

    #[Test]
    public function it_leaves_out_a_price_whose_billing_cycle_disagrees_with_its_tag(): void
    {
        Log::spy();

        $prices = $this->stripePrices(['monthly', 'yearly', 'lifetime']);
        $prices['data'][2]['type'] = 'recurring';
        $prices['data'][2]['recurring'] = ['interval' => 'month', 'interval_count' => 1];

        $this->fakeStripe(['GET prices' => $prices]);

        $this->assertSame(['price_monthly', 'price_yearly'], array_column(StripeCatalogue::offers(), 'price_id'));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_leaves_out_a_price_carrying_a_tag_it_does_not_know(): void
    {
        Log::spy();

        $prices = $this->stripePrices();
        $prices['data'][0]['metadata'] = ['offer' => 'weekly'];

        $this->fakeStripe(['GET prices' => $prices]);

        $this->assertSame(['price_yearly', 'price_lifetime'], array_column(StripeCatalogue::offers(), 'price_id'));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_ignores_an_untagged_price_without_saying_anything(): void
    {
        Log::spy();

        $prices = $this->stripePrices();
        $prices['data'][0]['metadata'] = [];

        $this->fakeStripe(['GET prices' => $prices]);

        $this->assertSame(['price_yearly', 'price_lifetime'], array_column(StripeCatalogue::offers(), 'price_id'));
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_reads_the_catalogue_once_an_hour(): void
    {
        $client = $this->fakeStripe();

        StripeCatalogue::offers();
        StripeCatalogue::offers();

        $this->assertCount(1, $client->requests('GET prices'));
    }

    #[Test]
    public function it_reads_stripe_again_rather_than_trusting_a_cache_of_another_shape(): void
    {
        $client = $this->fakeStripe();

        Cache::put('stripe_offers', ['whatever an older version wrote'], now()->addHour());

        $this->assertCount(3, StripeCatalogue::offers());
        $this->assertCount(1, $client->requests('GET prices'));
    }

    #[Test]
    public function it_logs_an_unreadable_catalogue_and_does_not_cache_the_failure(): void
    {
        Log::spy();

        $client = $this->fakeStripe();
        $client->failOn('prices*');

        $this->assertSame([], StripeCatalogue::offers());
        $this->assertFalse(StripeCatalogue::isAvailable());
        Log::shouldHaveReceived('warning')->atLeast()->once();

        $client->respondTo('GET prices', $this->stripePrices());

        $this->assertCount(3, StripeCatalogue::offers());
    }

    #[Test]
    public function it_names_the_kind_a_price_is(): void
    {
        $this->fakeStripe();

        $this->assertSame(OfferKindEnum::Yearly, StripeCatalogue::kindOf('price_yearly'));
        $this->assertNull(StripeCatalogue::kindOf('price_dundermifflin'));
    }

    #[Test]
    public function it_names_the_kind_a_subscription_is_on(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $subscription = $this->subscribeAccount($account, 'price_yearly');

        $this->assertSame(OfferKindEnum::Yearly, StripeCatalogue::kindOfSubscription($subscription));
    }

    #[Test]
    public function it_reads_what_a_payment_unlocked_off_the_payment_rather_than_off_the_tag(): void
    {
        $this->fakeStripe();

        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly');

        $purchase = Payment::factory()->create(['account_id' => $account->id, 'stripe_subscription_id' => null]);
        $renewal = Payment::factory()->create([
            'account_id' => $account->id,
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);
        $orphan = Payment::factory()->create([
            'account_id' => $account->id,
            'stripe_subscription_id' => 'sub_gone',
        ]);

        $this->assertSame(OfferKindEnum::Lifetime, StripeCatalogue::kindOfPayment($purchase));
        $this->assertSame(OfferKindEnum::Monthly, StripeCatalogue::kindOfPayment($renewal));
        $this->assertNull(StripeCatalogue::kindOfPayment($orphan));
    }

    #[Test]
    public function it_forgets_what_it_read(): void
    {
        $client = $this->fakeStripe();

        StripeCatalogue::offers();
        StripeCatalogue::forget();
        StripeCatalogue::offers();

        $this->assertCount(2, $client->requests('GET prices'));
    }
}
