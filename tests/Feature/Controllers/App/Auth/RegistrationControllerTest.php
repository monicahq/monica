<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\Account;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
}
