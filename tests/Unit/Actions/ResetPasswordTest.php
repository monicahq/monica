<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ResetPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_the_new_password(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'password' => Hash::make('beets'),
        ]);

        $result = new ResetPassword(
            email: 'michael.scott@dundermifflin.com',
            token: Password::createToken($user),
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_finds_the_account_whatever_the_case_of_the_address(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new ResetPassword(
            email: 'Michael.Scott@DunderMifflin.com',
            token: Password::createToken($user),
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password));
    }

    #[Test]
    public function it_throws_the_token_away_once_it_is_used(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new ResetPassword(
            email: 'michael.scott@dundermifflin.com',
            token: Password::createToken($user),
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_refuses_a_token_that_is_not_right(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('passwords.token'));

        new ResetPassword(
            email: 'michael.scott@dundermifflin.com',
            token: str_repeat('a', 64),
            password: 'bearsbeatsbattlestar',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_token_that_has_expired(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $token = Password::createToken($user);

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('passwords.token'));

        new ResetPassword(
            email: 'michael.scott@dundermifflin.com',
            token: $token,
            password: 'bearsbeatsbattlestar',
        )->execute();
    }

    #[Test]
    public function it_refuses_an_address_that_has_no_account_without_saying_so(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(__('passwords.token'));

        new ResetPassword(
            email: 'nobody@dundermifflin.com',
            token: str_repeat('a', 64),
            password: 'bearsbeatsbattlestar',
        )->execute();
    }
}
