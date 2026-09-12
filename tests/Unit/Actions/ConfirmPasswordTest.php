<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConfirmPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_confirms_the_right_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $confirmed = new ConfirmPassword(
            user: $user,
            password: 'dundermifflin',
        )->execute();

        $this->assertTrue($confirmed);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasswordConfirmed);
    }

    #[Test]
    public function it_refuses_the_wrong_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $confirmed = new ConfirmPassword(
            user: $user,
            password: 'scranton',
        )->execute();

        $this->assertFalse($confirmed);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasswordConfirmationRefused);
    }

    #[Test]
    public function it_refuses_an_account_that_has_no_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        $confirmed = new ConfirmPassword(
            user: $user,
            password: 'dundermifflin',
        )->execute();

        $this->assertFalse($confirmed);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasswordConfirmationRefused);
    }
}
