<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Listeners\HandlePasskeyRemoval;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasskeyRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Passkeys\Events\PasskeyDeleted;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandlePasskeyRemovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_removal_down_naming_the_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Pam iPad']);

        new HandlePasskeyRemoval()->handle(new PasskeyDeleted($user, $passkey));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyRemoved
                && $job->parameters === ['name' => 'Pam iPad'],
        );
    }

    #[Test]
    public function it_tells_the_owner_of_the_address(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Pam iPad']);

        new HandlePasskeyRemoval()->handle(new PasskeyDeleted($user, $passkey));

        Notification::assertSentTo(
            $user,
            PasskeyRemoved::class,
            fn (PasskeyRemoved $notification): bool => $notification->name === 'Pam iPad',
        );
    }

    #[Test]
    public function it_writes_the_removal_down_after_the_row_is_gone(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Pam iPad']);
        $passkey->delete();

        new HandlePasskeyRemoval()->handle(new PasskeyDeleted($user, $passkey));

        Queue::assertPushedOn(queue: 'low', job: LogUserAction::class);
        Notification::assertSentTo($user, PasskeyRemoved::class);
    }
}
