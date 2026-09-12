<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\PasswordSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
    public function it_lets_a_user_who_signs_in_through_a_provider_set_a_password(): void
    {
        Queue::fake();

        $user = User::factory()->singleSignOn()->create();

        new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertNotNull($user->refresh()->password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue($user->usesSingleSignOn());
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
        Notification::fake();

        $user = User::factory()->create(['password' => null]);

        new UpdateUserPassword(user: $user, password: 'thatswhatshesaid')->execute();

        Notification::assertSentTo(
            $user,
            PasswordSet::class,
            fn (PasswordSet $notification): bool => $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_says_nothing_when_an_existing_password_is_merely_changed(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();

        new UpdateUserPassword(user: $user, password: 'thatswhatshesaid')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate,
        );
        Notification::assertNothingSent();
    }
}
