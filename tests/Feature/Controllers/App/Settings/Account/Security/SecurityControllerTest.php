<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_security_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'password_changed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Security and access', escape: false);
        $response->assertSee('Password', escape: false);
        $response->assertSee('Current password', escape: false);
        $response->assertSee('Last changed 2 days ago', escape: false);
        $response->assertSee('Back to settings', escape: false);
    }

    #[Test]
    public function it_says_there_are_no_api_keys_when_none_have_been_made(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('API keys', escape: false);
        $response->assertSee('Active · no API keys', escape: false);
        $response->assertSee('No keys yet', escape: false);
    }

    #[Test]
    public function it_lists_the_api_keys(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Active · 1 API key', escape: false);
        $response->assertSee('Dundie awards bot', escape: false);
        $response->assertSee('never used', escape: false);
        $response->assertDontSee('No API keys yet', escape: false);
    }

    #[Test]
    public function it_leaves_out_the_date_when_the_password_was_never_changed(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Last changed', escape: false);
    }

    #[Test]
    public function it_says_there_is_nothing_to_change_when_the_account_signs_in_elsewhere(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('You sign in through your identity provider, so there is no password to change here.', escape: false);
        $response->assertDontSee('Current password', escape: false);
    }

    #[Test]
    public function it_offers_to_turn_two_factor_authentication_on_when_it_is_off(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Two factor authentication', escape: false);
        $response->assertSee('Authenticator app', escape: false);
        $response->assertSee('Turn it on', escape: false);
        $response->assertDontSee('Recovery codes', escape: false);
    }

    #[Test]
    public function it_shows_the_recovery_codes_when_two_factor_authentication_is_on(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_confirmed_at' => now()->subDays(3)]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('Turned on 3 days ago', escape: false);
        $response->assertSee('Recovery codes', escape: false);
        $response->assertSee('scranton-1', escape: false);
        $response->assertSee('scranton-2', escape: false);
    }

    #[Test]
    public function it_says_so_when_every_recovery_code_has_been_used(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_recovery_codes' => []]);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
        $response->assertSee('You have used every code. Ask for new ones before you need them.', escape: false);
    }

    #[Test]
    public function it_offers_to_copy_the_recovery_codes(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_confirmed_at' => now()->subDays(3)]);

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertSee('Copy to the clipboard', escape: false)
            ->assertSee('window.copyText(codes.join(', escape: false);
    }

    #[Test]
    public function it_does_not_offer_to_copy_recovery_codes_that_are_all_spent(): void
    {
        $user = User::factory()->twoFactor()->create(['two_factor_recovery_codes' => []]);

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertDontSee('window.copyText(codes.join(', escape: false);
    }

    #[Test]
    public function it_redirects_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('settings.security.index'));

        $response->assertRedirect(route('auth.login.new'));
    }
}
