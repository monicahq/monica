<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingCurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_remembers_the_currency_the_reader_picked(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)
            ->put(route('settings.billingCurrency.update'), ['currency' => 'usd']);

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHas('billing.currency', 'usd');
    }

    #[Test]
    public function it_refuses_a_currency_the_catalogue_does_not_quote(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)
            ->put(route('settings.billingCurrency.update'), ['currency' => 'chf']);

        $response->assertSessionHasErrors('currency');
        $response->assertSessionMissing('billing.currency');
    }
}
