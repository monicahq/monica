<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountLifetimeAccessControllerTest extends TestCase
{
    use RefreshDatabase;

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

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
