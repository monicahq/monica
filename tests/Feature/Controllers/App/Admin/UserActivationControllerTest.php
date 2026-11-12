<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserActivationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_user_signing_in(): void
    {
        $todd = User::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.activation.destroy', ['account' => $todd->account_id]));

        $response->assertRedirect(route('admin.accounts.show', ['account' => $todd->account_id]));
        $response->assertSessionHas('status', 'They can no longer sign in.');

        $this->assertFalse($todd->refresh()->is_active);
    }

    #[Test]
    public function it_ends_a_session_the_user_already_had_open(): void
    {
        $todd = User::factory()->create();

        $this->actingAs($todd)->get(route('vaults.index'))->assertStatus(200);

        $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.activation.destroy', ['account' => $todd->account_id]));

        $this->actingAs(User::query()->findOrFail($todd->id))
            ->get(route('vaults.index'))
            ->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_lets_a_user_back_in(): void
    {
        $todd = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.accounts.activation.create', ['account' => $todd->account_id]));

        $response->assertSessionHas('status', 'They can sign in again.');
        $this->assertTrue($todd->refresh()->is_active);

        $this->actingAs($todd->refresh())->get(route('vaults.index'))->assertStatus(200);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_deactivate_themselves(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)
            ->delete(route('admin.accounts.activation.destroy', ['account' => $michael->account_id]));

        $response->assertSessionHasErrors('activation');
        $this->assertTrue($michael->refresh()->is_active);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
