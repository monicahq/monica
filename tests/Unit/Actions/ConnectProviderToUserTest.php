<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConnectProviderToUser;
use App\Enums\SsoProviderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectProviderToUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_connects_the_provider(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $connected = new ConnectProviderToUser(
            user: $user,
            provider: SsoProviderEnum::GitHub,
            ssoId: '108120124',
        )->execute();

        $this->assertInstanceOf(User::class, $connected);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github', 'sso_id' => '108120124']);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserSsoConnected);
    }

    #[Test]
    public function it_leaves_the_password_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $password = $user->password;

        new ConnectProviderToUser(
            user: $user,
            provider: SsoProviderEnum::GitHub,
            ssoId: '108120124',
        )->execute();

        $this->assertEquals($password, $user->refresh()->password);
        $this->assertNotNull($user->password_changed_at);
    }

    #[Test]
    public function it_refuses_a_provider_account_already_connected_to_somebody_else(): void
    {
        Queue::fake();

        User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new ConnectProviderToUser(
            user: $user,
            provider: SsoProviderEnum::GitHub,
            ssoId: '108120124',
        )->execute();
    }

    #[Test]
    public function it_changes_nothing_on_either_account_when_the_provider_account_is_taken(): void
    {
        Queue::fake();

        $owner = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();
        $user = User::factory()->create();

        try {
            new ConnectProviderToUser(
                user: $user,
                provider: SsoProviderEnum::GitHub,
                ssoId: '108120124',
            )->execute();
        } catch (ValidationException) {
        }

        $this->assertDatabaseMissing('provider_connections', ['user_id' => $user->id]);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $owner->id, 'sso_id' => '108120124']);
    }

    #[Test]
    public function it_connects_a_second_provider(): void
    {
        Queue::fake();

        $user = User::factory()->singleSignOn()->create();

        new ConnectProviderToUser(
            user: $user,
            provider: SsoProviderEnum::GitHub,
            ssoId: '108120124',
        )->execute();

        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'google']);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github', 'sso_id' => '108120124']);
    }

    #[Test]
    public function it_refuses_a_provider_the_user_already_connected(): void
    {
        Queue::fake();

        $user = User::factory()->singleSignOn()->create();

        $this->expectException(ValidationException::class);

        new ConnectProviderToUser(
            user: $user,
            provider: SsoProviderEnum::Google,
            ssoId: '108120124',
        )->execute();
    }
}
