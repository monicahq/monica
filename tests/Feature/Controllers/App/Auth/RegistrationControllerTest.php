<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Http\Middleware\EnsureRegistrationIsEnabled;
use App\Models\Account;
use App\Models\PreviewListEntry;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
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
        $response->assertSee('Next step: validate your email address');
    }

    #[Test]
    public function it_offers_a_passkey_over_a_secure_connection(): void
    {
        URL::forceScheme('https');

        $response = $this->get(route('auth.register.new'));

        $response->assertSee('Use a passkey instead');
        $response->assertSee('Set a password instead');
    }

    #[Test]
    public function it_says_nothing_about_passkeys_over_a_connection_that_is_not_secure(): void
    {
        URL::forceScheme('http');

        $response = $this->get(route('auth.register.new'));

        $response->assertDontSee('Use a passkey instead');
        $response->assertDontSee('You will sign in with a passkey');
    }

    #[Test]
    public function it_creates_an_account_and_its_first_user(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.register.create'), $this->validPayload());

        $response->assertRedirect(route('auth.verification.notice'));
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertEquals(1, Account::query()->count());
        $this->assertTrue($user->is($user->account->user));
        $this->assertEquals('Michael Scott', $user->name);
    }

    #[Test]
    public function it_sends_the_verification_email(): void
    {
        Notification::fake();

        $this->post(route('auth.register.create'), $this->validPayload());

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            fn (VerifyEmail $notification): bool => $notification->queue === 'high',
        );
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
    public function it_stores_an_email_address_typed_with_capitals_in_lowercase(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'email' => 'Michael.Scott@DunderMifflin.com',
        ]));

        $response->assertRedirect(route('auth.verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'michael.scott@dundermifflin.com']);
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
    public function it_keeps_the_time_zone_the_browser_reported(): void
    {
        $this->post(route('auth.register.create'), $this->validPayload([
            'timezone' => 'America/New_York',
        ]));

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertEquals('America/New_York', $user->timezone);
    }

    #[Test]
    public function it_answers_not_found_while_registration_is_disabled(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->get(route('auth.register.new'))->assertNotFound();
        $this->post(route('auth.register.create'), $this->validPayload())->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'michael.scott@dundermifflin.com']);
        $this->assertGuest();
    }

    #[Test]
    public function it_creates_the_account_of_somebody_admitted_while_registration_is_closed(): void
    {
        Queue::fake();
        Notification::fake();
        config()->set('monica.registration_enabled', false);

        $entry = PreviewListEntry::factory()->admitted('creed-bratton-token')->create(['email' => 'creed.bratton@dundermifflin.com']);

        $response = $this->post(route('auth.register.create'), $this->validPayload([
            'email' => 'somebody.else@dundermifflin.com',
            'admission' => 'creed-bratton-token',
        ]));

        $response->assertRedirect(route('auth.verification.notice'));

        $user = User::query()->where('email', 'creed.bratton@dundermifflin.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($entry->refresh()->registered_at);
        $this->assertDatabaseMissing('users', ['email' => 'somebody.else@dundermifflin.com']);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    /** @return array<string, array{string}> */
    public static function unusableAdmissions(): array
    {
        return [
            'an unknown token' => ['unknown'],
            'an expired link' => ['expired'],
            'a used link' => ['used'],
        ];
    }

    #[Test]
    #[DataProvider('unusableAdmissions')]
    public function it_answers_not_found_for_an_admission_that_no_longer_opens_sign_up(string $state): void
    {
        config()->set('monica.registration_enabled', false);

        PreviewListEntry::factory()->admitted('creed-bratton-token')->create([
            'email' => 'creed.bratton@dundermifflin.com',
            'token_expires_at' => $state === 'expired' ? now()->subMinute() : now()->addDay(),
            'registered_at' => $state === 'used' ? now() : null,
        ]);

        $admission = $state === 'unknown' ? 'kevin-malone-token' : 'creed-bratton-token';

        $this->post(route('auth.register.create'), $this->validPayload(['admission' => $admission]))
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'creed.bratton@dundermifflin.com']);
    }

    #[Test]
    public function it_answers_not_found_for_an_admission_a_concurrent_sign_up_used(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->withoutMiddleware(EnsureRegistrationIsEnabled::class)
            ->post(route('auth.register.create'), $this->validPayload(['admission' => 'creed-bratton-token']))
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'michael.scott@dundermifflin.com']);
    }
}
