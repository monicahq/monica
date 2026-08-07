<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_sign_in_page(): void
    {
        $response = $this->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
    }

    #[Test]
    public function it_signs_a_user_in(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_a_wrong_password(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_suspended_user(): void
    {
        Queue::fake();

        User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_sends_somebody_who_uses_two_factor_to_the_challenge(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('auth.twoFactor.new'));
        $response->assertSessionHas('twoFactor.user.id', $user->id);

        // Knowing the password is not enough on its own.
        $this->assertGuest();
    }

    #[Test]
    public function it_does_not_show_the_human_check_when_it_is_turned_off(): void
    {
        $this->get(route('auth.login.new'))
            ->assertOk()
            ->assertDontSee('cf-turnstile');
    }

    #[Test]
    public function it_shows_the_human_check_when_it_is_turned_on(): void
    {
        config(['turnstile.enabled' => true]);
        config(['turnstile.site_key' => 'the-key-of-the-one-with-the-widget']);

        $this->get(route('auth.login.new'))
            ->assertOk()
            ->assertSee('cf-turnstile')
            ->assertSee('the-key-of-the-one-with-the-widget');
    }

    #[Test]
    public function it_refuses_a_sign_in_without_a_human_check(): void
    {
        config(['turnstile.enabled' => true]);
        Http::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function it_signs_a_user_in_when_cloudflare_accepts_the_human_check(): void
    {
        Queue::fake();
        config(['turnstile.enabled' => true]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'cf-turnstile-response' => 'a-token-cloudflare-likes',
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_the_sign_in_when_cloudflare_rejects_the_human_check(): void
    {
        config(['turnstile.enabled' => true]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.login.create'), [
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'password',
            'cf-turnstile-response' => 'a-token-that-was-already-used',
        ]);

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function it_links_to_the_other_ways_in(): void
    {
        $this->get(route('auth.login.new'))
            ->assertOk()
            ->assertSee(route('auth.magicLink.new'))
            ->assertSee(route('auth.password.new'))
            ->assertSee(route('auth.register.new'));
    }

    #[Test]
    public function it_signs_a_user_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('auth.login.destroy'));

        $response->assertRedirect(route('home.index'));
        $this->assertGuest();
    }

    #[Test]
    public function it_keeps_somebody_who_is_signed_in_away_from_the_sign_in_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('auth.login.new'));

        $response->assertRedirect(route('home.index'));
    }
}
