<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserPassword;
use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_password(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'password' => Hash::make('beets'),
            'password_changed_at' => null,
        ]);

        $result = new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password));

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->refresh()->password_changed_at?->timestamp,
            2,
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_throws_when_the_user_signs_in_through_sso(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->singleSignOn()->create();

        new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();
    }

    #[Test]
    public function it_records_a_first_password_as_being_set_rather_than_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        new UpdateUserPassword(user: $user, password: 'thatswhatshesaid')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordSet,
        );
    }

    #[Test]
    public function it_tells_the_owner_when_a_password_appears_where_there_was_none(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        new UpdateUserPassword(user: $user, password: 'thatswhatshesaid')->execute();

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::PasswordSet,
        );
    }

    #[Test]
    public function it_says_nothing_when_an_existing_password_is_merely_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new UpdateUserPassword(user: $user, password: 'thatswhatshesaid')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate,
        );
        Queue::assertNotPushed(SendEmail::class);
    }
}
