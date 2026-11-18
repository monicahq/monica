<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Actions\CheckStripeConnection;
use App\Helpers\StripeCatalogue;
use App\ViewModels\Admin\BillingStatusViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class BillingStatusViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_a_self_hosted_instance_takes_no_payment(): void
    {
        config(['monica.self_hosted' => true]);

        $viewModel = new BillingStatusViewModel(connection: null);

        $this->assertTrue($viewModel->isSelfHosted());
    }

    #[Test]
    public function it_lists_the_offers_with_their_amount_in_each_currency(): void
    {
        $this->fakeReachableStripe();

        $offers = new BillingStatusViewModel(connection: new CheckStripeConnection()->execute())->offers();

        $this->assertSame(['Monthly subscription', 'Yearly subscription', 'Lifetime access'], array_column($offers, 'kind'));
        $this->assertNotSame([], $offers[0]['amounts']);
    }

    #[Test]
    public function it_names_the_kinds_of_offer_with_no_price_on_sale(): void
    {
        $this->fakeReachableStripe(kinds: ['monthly']);

        $viewModel = new BillingStatusViewModel(connection: new CheckStripeConnection()->execute());

        $this->assertSame(['Yearly subscription', 'Lifetime access'], $viewModel->kindsWithoutPrice());
    }

    #[Test]
    public function it_tells_an_unreadable_catalogue_apart_from_an_empty_one(): void
    {
        $client = $this->fakeReachableStripe(kinds: []);

        $this->assertTrue(new BillingStatusViewModel(connection: new CheckStripeConnection()->execute())->isCatalogueAvailable());

        StripeCatalogue::forget();
        $client->failOn('prices*');

        $this->assertFalse(new BillingStatusViewModel(connection: new CheckStripeConnection()->execute())->isCatalogueAvailable());
    }

    #[Test]
    public function it_is_not_reachable_when_stripe_does_not_answer(): void
    {
        config(['cashier.secret' => 'sk_test_scranton']);
        Http::fake(['api.stripe.com/*' => fn () => throw new ConnectionException('timed out')]);

        $viewModel = new BillingStatusViewModel(connection: new CheckStripeConnection()->execute());

        $this->assertFalse($viewModel->isReachable());
    }

    #[Test]
    public function it_names_each_missing_confirmation_with_what_goes_wrong_without_it(): void
    {
        $this->fakeReachableStripe(endpoints: [[
            'url' => route('cashier.webhook'),
            'status' => 'enabled',
            'enabled_events' => array_values(array_diff(config('cashier.webhook.events'), ['invoice.paid'])),
        ]]);

        $viewModel = new BillingStatusViewModel(connection: new CheckStripeConnection()->execute());

        $this->assertTrue($viewModel->hasEnabledEndpoint());
        $this->assertSame([['event' => 'invoice.paid', 'consequence' => 'No payment is written down, so no screen shows what was charged.']], $viewModel->missingEvents());
    }

    #[Test]
    public function it_shows_the_figures_when_stripe_is_unreachable(): void
    {
        config(['cashier.secret' => 'sk_test_scranton']);
        Http::fake(['api.stripe.com/*' => fn () => throw new ConnectionException('timed out')]);

        $viewModel = new BillingStatusViewModel(connection: new CheckStripeConnection()->execute());

        $this->assertSame(0, $viewModel->figures()->access()['none']);
    }

    /**
     * @param  list<string>  $kinds
     * @param  list<array<string, mixed>>  $endpoints
     */
    private function fakeReachableStripe(array $kinds = ['monthly', 'yearly', 'lifetime'], array $endpoints = []): FakeStripeClient
    {
        $client = $this->fakeStripe(kinds: $kinds);
        Http::fake(['api.stripe.com/v1/webhook_endpoints*' => Http::response(['object' => 'list', 'data' => $endpoints])]);

        return $client;
    }
}
