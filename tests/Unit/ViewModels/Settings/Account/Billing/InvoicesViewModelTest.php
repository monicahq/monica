<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Billing;

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
