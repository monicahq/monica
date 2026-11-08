<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DisconnectProviderFromUser;
use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\ProviderConnection;
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

        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        new DisconnectProviderFromUser(user: $user, provider: SsoProviderEnum::GitHub)->execute();

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
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

        new DisconnectProviderFromUser(user: $user->refresh(), provider: SsoProviderEnum::Google)->execute();

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_disconnects_one_provider_while_another_one_signs_the_user_in(): void
    {
        Queue::fake();
        Notification::fake();
        config([
            'monica.sso_installed' => SsoProviderEnum::values(),
            'services.github.client_id' => 'dunder-mifflin-scranton',
            'services.github.client_secret' => 'that-is-what-she-said',
            'services.github.redirect' => 'https://monica.test/sso/github/callback',
        ]);

        $user = User::factory()->singleSignOn()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        new DisconnectProviderFromUser(user: $user, provider: SsoProviderEnum::Google)->execute();

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id, 'provider' => 'google']);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github']);
    }

    #[Test]
    public function it_refuses_to_leave_the_user_no_way_to_sign_in(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();

        $this->expectException(ValidationException::class);

        new DisconnectProviderFromUser(user: $user, provider: SsoProviderEnum::Google)->execute();
    }

    #[Test]
    public function it_keeps_the_provider_when_it_refuses(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->singleSignOn()->create();

        try {
            new DisconnectProviderFromUser(user: $user, provider: SsoProviderEnum::Google)->execute();
        } catch (ValidationException) {
        }

        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'google']);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_refuses_a_user_who_signs_in_through_no_provider(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new DisconnectProviderFromUser(user: User::factory()->create(), provider: SsoProviderEnum::GitHub)->execute();
    }
}
