<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Billing;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\User;
use App\ViewModels\Settings\Account\Billing\InvoicesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesViewModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_lists_every_payment_newest_first(): void
    {
        $michael = User::factory()->create();

        $older = Payment::factory()->create(['account_id' => $michael->account_id, 'billed_at' => now()->subYear()]);
        $newer = Payment::factory()->create(['account_id' => $michael->account_id, 'billed_at' => now()]);

        $payments = new InvoicesViewModel(user: $michael)->payments();

        $this->assertSame([$newer->id, $older->id], $payments->pluck('id')->all());
    }

    #[Test]
    public function it_shows_twenty_payments_a_page(): void
    {
        $michael = User::factory()->create();

        for ($index = 1; $index <= 25; $index++) {
            Payment::factory()->create(['account_id' => $michael->account_id, 'billed_at' => now()->subDays($index)]);
        }

        $firstPage = new InvoicesViewModel(user: $michael)->payments();

        $this->assertSame(25, $firstPage->total());
        $this->assertCount(20, $firstPage);

        $this->app['request']->merge(['page' => 2]);

        $secondPage = new InvoicesViewModel(user: $michael)->payments();

        $this->assertCount(5, $secondPage);
        $this->assertTrue($secondPage[0]->billed_at->isSameDay(now()->subDays(21)));
    }

    #[Test]
    public function it_lists_nothing_for_an_account_that_never_paid(): void
    {
        $michael = User::factory()->create();

        $this->assertTrue(new InvoicesViewModel(user: $michael)->payments()->isEmpty());
    }

    #[Test]
    public function it_never_lists_another_account_payments(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Payment::factory()->create(['account_id' => $dwight->account_id]);

        $this->assertTrue(new InvoicesViewModel(user: $michael)->payments()->isEmpty());
    }

    #[Test]
    public function it_names_what_each_payment_was_for(): void
    {
        $michael = User::factory()->create();

        $purchase = Payment::factory()->create(['account_id' => $michael->account_id, 'kind' => OfferKindEnum::Lifetime]);
        $renewal = Payment::factory()->forSubscription('yearly')->create(['account_id' => $michael->account_id]);

        $viewModel = new InvoicesViewModel(user: $michael);

        $this->assertSame('Lifetime access', $viewModel->paymentLabel($purchase));
        $this->assertSame('Yearly subscription', $viewModel->paymentLabel($renewal));
    }

    #[Test]
    public function it_falls_back_to_a_plain_word_when_nothing_names_the_offer(): void
    {
        $michael = User::factory()->create();

        $payment = Payment::factory()->create([
            'account_id' => $michael->account_id,
            'kind' => null,
            'stripe_subscription_id' => 'sub_gone',
        ]);

        $this->assertSame('Subscription', new InvoicesViewModel(user: $michael)->paymentLabel($payment));
    }

    #[Test]
    public function it_writes_the_amount_for_the_reader(): void
    {
        $michael = User::factory()->create();
        $payment = Payment::factory()->create(['account_id' => $michael->account_id, 'total' => 19900, 'currency' => 'eur']);

        $this->app->setLocale('en');
        $this->assertSame('€199.00', $payment->total());

        $this->app->setLocale('fr_FR');
        $this->assertStringContainsString('199,00', $payment->total());
    }
}
