<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyUserPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\PasswordRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function overASecureConnection(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');
    }

    private function userWithAPasskey(): User
    {
        $this->overASecureConnection();

        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    #[Test]
    public function it_removes_the_password(): void
    {
        Queue::fake();

        $user = $this->userWithAPasskey();

        new DestroyUserPassword(user: $user)->execute();

        $user->refresh();
        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->usesPasswordlessSignIn());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordRemoved,
        );
    }

    #[Test]
    public function it_tells_the_owner_of_the_address(): void
    {
        Notification::fake();

        $user = $this->userWithAPasskey();

        new DestroyUserPassword(user: $user)->execute();

        Notification::assertSentTo(
            $user,
            PasswordRemoved::class,
            fn (PasswordRemoved $notification): bool => $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_refuses_an_account_that_holds_no_passkey(): void
    {
        Queue::fake();
        Notification::fake();
        $this->overASecureConnection();

        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        try {
            new DestroyUserPassword(user: $user)->execute();
        } finally {
            $this->assertNotNull($user->refresh()->password);
            Queue::assertNothingPushed();
            Notification::assertNothingSent();
        }
    }

    #[Test]
    public function it_refuses_where_a_passkey_could_not_be_used_anyway(): void
    {
        Queue::fake();

        $this->app['request']->server->set('HTTPS', 'off');

        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->expectException(ValidationException::class);

        try {
            new DestroyUserPassword(user: $user)->execute();
        } finally {
            $this->assertNotNull($user->refresh()->password);
        }
    }

    #[Test]
    public function it_refuses_an_account_that_signs_in_through_an_identity_provider(): void
    {
        Queue::fake();
        $this->overASecureConnection();

        $this->expectException(ValidationException::class);

        new DestroyUserPassword(user: User::factory()->singleSignOn()->create())->execute();
    }

    #[Test]
    public function it_refuses_an_account_that_already_has_no_password(): void
    {
        Queue::fake();
        $this->overASecureConnection();

        $user = User::factory()->create(['password' => null]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->expectException(ValidationException::class);

        new DestroyUserPassword(user: $user)->execute();
    }
}
