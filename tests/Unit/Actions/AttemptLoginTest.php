<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AttemptLogin;
use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttemptLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_a_user_in(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $result = new AttemptLogin(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            ip: '10.0.0.1',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: CheckLastLogin::class,
            callback: fn (CheckLastLogin $job): bool => $job->user->id === $user->id && $job->ip === '10.0.0.1',
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserLogin,
        );
    }

    #[Test]
    public function it_matches_the_address_whatever_the_casing(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new AttemptLogin(
            email: 'Michael.Scott@DunderMifflin.com',
            password: 'password',
        )->execute();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_a_wrong_password_and_warns_the_owner(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        try {
            new AttemptLogin(
                email: 'michael.scott@dundermifflin.com',
                password: 'thatswhatshesaid',
            )->execute();

            $this->fail('The action should have refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertGuest();

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::LoginFailed
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_says_nothing_about_an_address_that_has_no_account(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        try {
            new AttemptLogin(
                email: 'nobody@dundermifflin.com',
                password: 'password',
            )->execute();
        } finally {
            Queue::assertNotPushed(SendEmail::class);
        }
    }

    #[Test]
    public function it_refuses_a_suspended_user(): void
    {
        Queue::fake();

        $user = User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->expectException(ValidationException::class);

        try {
            new AttemptLogin(
                email: 'michael.scott@dundermifflin.com',
                password: 'password',
            )->execute();
        } finally {
            $this->assertGuest();
            Queue::assertPushed(
                SendEmail::class,
                fn (SendEmail $job): bool => $job->user->id === $user->id,
            );
        }
    }

    #[Test]
    public function it_refuses_somebody_who_signs_in_through_single_sign_on(): void
    {
        Queue::fake();

        User::factory()->singleSignOn()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->expectException(ValidationException::class);

        try {
            new AttemptLogin(
                email: 'michael.scott@dundermifflin.com',
                password: 'password',
            )->execute();
        } finally {
            $this->assertGuest();
        }
    }

    #[Test]
    public function it_stops_trying_after_five_failures(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                new AttemptLogin(
                    email: 'michael.scott@dundermifflin.com',
                    password: 'wrong',
                    ip: '10.0.0.1',
                )->execute();
            } catch (ValidationException) {
            }
        }

        // The sixth is refused before the password is even looked at, so even
        // the right one does not get through.
        try {
            new AttemptLogin(
                email: 'michael.scott@dundermifflin.com',
                password: 'password',
                ip: '10.0.0.1',
            )->execute();

            $this->fail('The action should have been rate limited.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Too many sign-in attempts', $exception->errors()['email'][0]);
        }

        $this->assertGuest();
    }

    #[Test]
    public function it_forgets_the_failures_once_somebody_gets_in(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        try {
            new AttemptLogin(
                email: 'michael.scott@dundermifflin.com',
                password: 'wrong',
                ip: '10.0.0.1',
            )->execute();
        } catch (ValidationException) {
        }

        new AttemptLogin(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            ip: '10.0.0.1',
        )->execute();

        $this->assertEquals(0, RateLimiter::attempts('michael.scott@dundermifflin.com|10.0.0.1'));
    }

    #[Test]
    public function it_writes_a_remember_token_when_asked_to(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'remember_token' => null,
        ]);

        $user = new AttemptLogin(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            remember: true,
        )->execute();

        $this->assertNotNull($user->refresh()->remember_token);
    }

    #[Test]
    public function it_leaves_no_remember_token_when_not_asked_to(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'remember_token' => null,
        ]);

        $user = new AttemptLogin(
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
        )->execute();

        $this->assertNull($user->refresh()->remember_token);
    }
}
