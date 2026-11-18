<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_billing_screen_to_an_administrator(): void
    {
        Queue::fake();
        $this->fakeStripe();
        Http::fake(['api.stripe.com/v1/webhook_endpoints*' => Http::response(['object' => 'list', 'data' => []])]);
        Payment::factory()->create();
        StripeEvent::factory()->create(['type' => 'invoice.paid']);

        $this->actingAs($this->administrator())
            ->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee('Whether this instance can take a payment through Stripe')
            ->assertSee('Stripe holds no enabled endpoint for this instance.')
            ->assertSee('Amounts are shown as invoiced, tax included. Refunds are not subtracted.')
            ->assertSee(route('admin.billing.catalogueReload.create'), escape: false)
            ->assertDontSee('sk_test_dundermifflin');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_shows_one_sentence_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true, 'cashier.secret' => 'sk_test_dundermifflin']);
        Http::fake();

        $this->actingAs($this->administrator())
            ->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSee('This instance takes no payment, so there is nothing to show about Stripe.')
            ->assertDontSee('What billing brings in');

        Http::assertNothingSent();
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
