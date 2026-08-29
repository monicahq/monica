<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Listeners\HandlePasskeyLogin;
use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use LaravelWebauthn\Events\WebauthnLogin;
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

        new HandlePasskeyLogin()->handle(new WebauthnLogin($user));

        $this->assertNotNull($user->refresh()->last_login_at);
    }

    #[Test]
    public function it_looks_at_where_the_sign_in_came_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new HandlePasskeyLogin()->handle(new WebauthnLogin($user));

        Queue::assertPushedOn(queue: 'low', job: CheckLastLogin::class);
    }

    #[Test]
    public function it_writes_the_sign_in_down_naming_the_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        WebauthnKey::factory()->used()->create(['user_id' => $user->id, 'name' => 'Dwight iPhone']);

        new HandlePasskeyLogin()->handle(new WebauthnLogin($user));

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
        Queue::fake();

        $user = User::factory()->create();

        new HandlePasskeyLogin()->handle(new WebauthnLogin($user));

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::NewLogin,
        );
    }
}
