<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\StripeOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountLifetimeAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();
        $todd = User::factory()->create();

        $this->actingAs($jim)
            ->post(route('admin.accounts.lifetimeAccess.create', ['account' => $todd->account_id]))
            ->assertNotFound();

        $this->assertFalse($todd->account->refresh()->hasFullAccess());
    }

    #[Test]
    public function it_grants_lifetime_access_straight_away(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.accounts.lifetimeAccess.create', ['account' => $todd->account_id]));

        $response->assertRedirect(route('admin.accounts.show', ['account' => $todd->account_id]));
        $response->assertSessionHas('status', 'The account has lifetime access.');

        $this->assertTrue($todd->account->refresh()->hasFullAccess());
        $this->assertDatabaseHas('administrator_logs', [
            'action' => 'lifetime_access_granted',
            'account_id' => $todd->account_id,
        ]);
    }

    #[Test]
    public function it_withdraws_lifetime_access(): void
    {
        $todd = User::factory()->create();
        $todd->account->update(['access_granted_at' => now()]);

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.lifetimeAccess.destroy', ['account' => $todd->account_id]));

        $response->assertSessionHas('status', 'Lifetime access is withdrawn.');

        $this->assertFalse($todd->account->refresh()->hasFullAccess());
        $this->assertDatabaseHas('administrator_logs', ['action' => 'lifetime_access_withdrawn']);
    }

    #[Test]
    public function it_marks_a_paid_order_refunded_when_access_is_withdrawn(): void
    {
        $todd = User::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $todd->account_id]);

        $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.lifetimeAccess.destroy', ['account' => $todd->account_id]));

        $this->assertFalse($todd->account->refresh()->hasFullAccess());
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
    }

    #[Test]
    public function it_writes_no_order_when_access_is_granted(): void
    {
        $todd = User::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.accounts.lifetimeAccess.create', ['account' => $todd->account_id]));

        $this->assertSame(0, StripeOrder::query()->count());
        $this->assertNotNull($todd->account->refresh()->access_granted_at);
    }

    #[Test]
    public function it_lets_an_administrator_grant_it_to_their_own_account(): void
    {
        $michael = $this->administrator();

        $this->actingAs($michael)
            ->post(route('admin.accounts.lifetimeAccess.create', ['account' => $michael->account_id]));

        $this->assertTrue($michael->account->refresh()->hasFullAccess());
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
