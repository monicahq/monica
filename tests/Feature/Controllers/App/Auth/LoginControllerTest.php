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
    public function it_offers_to_remember_the_browser_ticked_by_default(): void
    {
        $response = $this->get(route('auth.login.new'));

        $this->assertMatchesRegularExpression('/<input[^>]*id="remember"[^>]*\schecked/s', $response->getContent());
    }

    #[Test]
    public function it_keeps_the_remember_box_unticked_when_a_failed_sign_in_left_it_so(): void
    {
        User::factory()->create(['email' => 'dwight@dundermifflin.com']);

        $response = $this->from(route('auth.login.new'))
            ->followingRedirects()
            ->post(route('auth.login.create'), [
                'email' => 'dwight@dundermifflin.com',
                'password' => 'bears-beets-battlestar',
            ]);

        $this->assertMatchesRegularExpression('/<input[^>]*id="remember"/s', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="remember"[^>]*\schecked/s', $response->getContent());
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

        $this->assertGuest();
    }

    #[Test]
    public function it_forgets_a_passwordless_sign_in_left_unfinished_before_the_password(): void
    {
        Queue::fake();

        User::factory()->twoFactor()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->withSession(['twoFactor.signInMethod' => 'email_link'])
            ->post(route('auth.login.create'), [
                'email' => 'michael.scott@dundermifflin.com',
                'password' => 'password',
            ]);

        $response->assertSessionMissing('twoFactor.signInMethod');
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
    public function it_hides_the_magic_link_offer_while_the_setting_is_off(): void
    {
        config()->set('monica.magic_link_enabled', false);

        $this->get(route('auth.login.new'))
            ->assertOk()
            ->assertDontSee('Send me a link instead');
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
