<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisconnectProviderFromUser;
use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use App\Notifications\SignInProviderDisconnected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisconnectProviderFromUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_disconnects_the_provider_of_somebody_who_still_has_a_password(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

        new DisconnectProviderFromUser(user: $user)->execute();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'sso_provider' => null,
            'sso_id' => null,
        ]);
        $this->assertNotNull($user->refresh()->password);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserSsoDisconnected);
        Notification::assertSentTo($user, SignInProviderDisconnected::class);
    }

    #[Test]
    public function it_disconnects_the_provider_of_somebody_who_still_has_a_passkey(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        new DisconnectProviderFromUser(user: $user->refresh())->execute();

        $this->assertNull($user->refresh()->sso_provider);
    }

    #[Test]
    public function it_refuses_to_leave_the_user_no_way_to_sign_in(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();

        $this->expectException(ValidationException::class);

        new DisconnectProviderFromUser(user: $user)->execute();
    }

    #[Test]
    public function it_keeps_the_provider_when_it_refuses(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();

        try {
            new DisconnectProviderFromUser(user: $user)->execute();
        } catch (ValidationException) {
        }

        $this->assertEquals(SsoProviderEnum::Google, $user->refresh()->sso_provider);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_user_who_signs_in_through_no_provider(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new DisconnectProviderFromUser(user: User::factory()->create())->execute();
    }
}
