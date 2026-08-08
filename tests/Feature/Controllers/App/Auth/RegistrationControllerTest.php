<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'thatswhatshesaid',
            'password_confirmation' => 'thatswhatshesaid',
            'terms' => '1',
        ], $overrides);
    }

    #[Test]
    public function it_shows_the_registration_page(): void
    {
        $response = $this->get(route('auth.register.new'));

        $response->assertStatus(200);
        $response->assertSee('Create your account');
    }

    #[Test]
    public function it_links_to_the_terms_of_use_and_the_privacy_policy(): void
    {
        $response = $this->get(route('auth.register.new'));

        $response->assertSee(config('monica.terms_url'));
        $response->assertSee(config('monica.privacy_url'));
    }

    #[Test]
    public function it_creates_an_account_and_its_first_user(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.register.create'), $this->validPayload());

        $response->assertRedirect(route('auth.verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertEquals(1, Account::query()->count());
        $this->assertTrue($user->is($user->account->user));
        $this->assertEquals('Michael Scott', $user->name);
    }

    #[Test]
    public function it_sends_the_verification_email_and_records_it(): void
    {
        $this->post(route('auth.register.create'), $this->validPayload());

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertDatabaseHas('emails_sent', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email_type' => EmailType::EmailVerification->value,
            'email_address' => 'michael.scott@dundermifflin.com',
            'subject' => 'Confirm your email address',
        ]);
    }

    #[Test]
    public function it_refuses_somebody_who_has_not_agreed_to_the_terms(): void
    {
        $response = $this->post(route('auth.register.create'), $this->validPayload(['terms' => '']));

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
        $this->assertEquals(0, User::query()->count());
    }

    #[Test]
    public function it_refuses_an_email_address_that_is_already_taken(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.register.create'), $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_password_that_is_too_short(): void
    {
        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_password_that_is_not_confirmed(): void
    {
        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'password_confirmation' => 'bearsbeatsbattlestar',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    #[Test]
    public function it_refuses_a_registration_without_a_human_check(): void
    {
        config(['turnstile.enabled' => true]);
        Http::fake();

        $response = $this->post(route('auth.register.create'), $this->validPayload());

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }

    #[Test]
    public function it_creates_the_account_when_cloudflare_accepts_the_human_check(): void
    {
        Queue::fake();
        config(['turnstile.enabled' => true]);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'cf-turnstile-response' => 'a-token-cloudflare-likes',
        ]));

        $response->assertRedirect(route('auth.verification.notice'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function it_refuses_the_registration_when_cloudflare_rejects_the_human_check(): void
    {
        config(['turnstile.enabled' => true]);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'cf-turnstile-response' => 'a-token-cloudflare-does-not-like',
        ]));

        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertGuest();
    }
}
