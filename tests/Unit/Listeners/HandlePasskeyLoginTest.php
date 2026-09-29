<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\SignInMethodEnum;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Listeners\HandlePasskeyLogin;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Passkeys\Events\PasskeyVerified;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandlePasskeyLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stamps_when_they_last_signed_in(): void
    {
        Queue::fake();

        $user = User::factory()->create(['last_login_at' => null]);

        new HandlePasskeyLogin()->handle(new PasskeyVerified($user, Passkey::factory()->create(['user_id' => $user->id])));

        $this->assertNotNull($user->refresh()->last_login_at);
    }

    #[Test]
    public function it_looks_at_where_the_sign_in_came_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new HandlePasskeyLogin()->handle(new PasskeyVerified($user, Passkey::factory()->create(['user_id' => $user->id])));

        Queue::assertPushedOn(queue: 'low', job: CheckLastLogin::class);
    }

    #[Test]
    public function it_writes_the_sign_in_down_naming_the_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = Passkey::factory()->used()->create(['user_id' => $user->id, 'name' => 'Dwight iPhone']);

        new HandlePasskeyLogin()->handle(new PasskeyVerified($user, $passkey));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyLogin
                && $job->parameters === ['name' => 'Dwight iPhone'],
        );
    }

    #[Test]
    public function it_tells_the_owner_of_the_address(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        new HandlePasskeyLogin()->handle(new PasskeyVerified($user, Passkey::factory()->create(['user_id' => $user->id])));

        Notification::assertSentTo(
            $user,
            NewLoginDetected::class,
            fn (NewLoginDetected $notification): bool => $notification->method === SignInMethodEnum::Passkey
                && $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_records_a_confirmation_rather_than_a_sign_in(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create(['last_login_at' => null]);
        $passkey = Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Scranton laptop']);

        $this->actingAs($user);

        new HandlePasskeyLogin()->handle(new PasskeyVerified($user, $passkey));

        $this->assertNull($user->refresh()->last_login_at);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyConfirmed);
        Queue::assertNotPushed(CheckLastLogin::class);
        Notification::assertNothingSent();
    }
}
