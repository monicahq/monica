<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecoveryCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_out_new_codes_to_an_account_without_a_password_without_asking_for_one(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'password' => null,
            'two_factor_recovery_codes' => ['scranton-1'],
        ]);
        SecurityKey::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertRedirect(route('settings.recoveryCodes.show'));
        $this->assertCount(8, $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_hands_out_a_new_set_of_recovery_codes(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertRedirect(route('settings.recoveryCodes.show'));
        $response->assertSessionHas('status', 'Your recovery codes are new.');

        $this->assertCount(8, $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_is_not_found_when_two_factor_authentication_is_not_in_use(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertNotFound();
    }

    #[Test]
    public function it_refuses_new_codes_without_a_confirmed_password(): void
    {
        $user = User::factory()->twoFactor()->create();
        $before = $user->two_factor_recovery_codes;

        $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertEquals($before, $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_shows_the_codes_once_after_regenerating(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertRedirect(route('settings.recoveryCodes.show'));

        $this->followRedirects($response)
            ->assertStatus(200)
            ->assertSee($user->refresh()->two_factor_recovery_codes[0], escape: false);
    }

    #[Test]
    public function it_shows_nothing_on_a_second_visit(): void
    {
        $user = User::factory()->twoFactor()->create();

        $response = $this->actingAs($user)->get(route('settings.recoveryCodes.show'));

        $response->assertRedirect(route('settings.security.index'));
    }
}
