<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Listeners\HandlePasskeyRegistration;
use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use LaravelWebauthn\Events\WebauthnRegister;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandlePasskeyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_addition_down_naming_the_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id, 'name' => 'Yubikey in a drawer']);

        new HandlePasskeyRegistration()->handle(new WebauthnRegister($passkey));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyAdded
                && $job->parameters === ['name' => 'Yubikey in a drawer']
                && $job->isAbout($passkey),
        );
    }

    #[Test]
    public function it_tells_the_owner_of_the_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id]);

        new HandlePasskeyRegistration()->handle(new WebauthnRegister($passkey));

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::PasskeyAdded,
        );
    }
}
