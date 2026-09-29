<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SignInWithProvider;
use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\User;
use App\Notifications\NewLoginDetected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as ProviderUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignInWithProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_signs_in_the_user_the_pair_belongs_to(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

        $signedIn = new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(),
        )->execute();

        $this->assertTrue($signedIn->is($user));
        $this->assertEquals(1, User::query()->count());
        Queue::assertPushedOn('low', LogUserAction::class);
        Queue::assertPushedOn('low', CheckLastLogin::class);
        Notification::assertSentTo(
            $user,
            NewLoginDetected::class,
            fn (NewLoginDetected $notification): bool => $notification->method === SignInMethodEnum::Provider
                && $notification->provider === SsoProviderEnum::GitHub,
        );
    }

    #[Test]
    public function it_waits_for_the_second_factor_before_warning_the_user(): void
    {
        Queue::fake();
        Notification::fake();

        User::factory()->twoFactor()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

        new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(),
        )->execute();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_recognises_the_user_after_they_changed_their_address_at_the_provider(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'michael.scott@dundermifflin.com',
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

        $signedIn = new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(email: 'prisonmike@dundermifflin.com'),
        )->execute();

        $this->assertTrue($signedIn->is($user));
        $this->assertEquals(1, User::query()->count());
    }

    #[Test]
    public function it_creates_an_account_for_somebody_the_instance_has_never_seen(): void
    {
        Queue::fake();
        Notification::fake();

        $signedIn = new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(),
        )->execute();

        $this->assertDatabaseHas('users', [
            'id' => $signedIn->id,
            'email' => 'michael.scott@dundermifflin.com',
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'sso_provider' => 'github',
            'sso_id' => '108120124',
            'password' => null,
        ]);
        $this->assertNull($signedIn->email_verified_at);
        Queue::assertPushedOn('low', LogUserAction::class);
    }

    #[Test]
    public function it_refuses_somebody_the_provider_gave_no_address_for(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(email: null),
        )->execute();
    }

    #[Test]
    public function it_creates_nothing_when_the_address_is_refused(): void
    {
        Queue::fake();

        try {
            new SignInWithProvider(
                provider: SsoProviderEnum::GitHub,
                providerUser: $this->providerUser(email: null),
            )->execute();
        } catch (ValidationException) {
        }

        $this->assertEquals(0, User::query()->count());
    }

    #[Test]
    public function it_refuses_an_address_that_already_belongs_to_somebody_signing_in_another_way(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->expectException(ValidationException::class);

        new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(),
        )->execute();
    }

    #[Test]
    public function it_connects_nothing_to_the_account_whose_address_was_claimed(): void
    {
        Queue::fake();

        $existing = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        try {
            new SignInWithProvider(
                provider: SsoProviderEnum::GitHub,
                providerUser: $this->providerUser(),
            )->execute();
        } catch (ValidationException) {
        }

        $this->assertNull($existing->refresh()->sso_provider);
        $this->assertNull($existing->sso_id);
        $this->assertEquals(1, User::query()->count());
    }

    #[Test]
    public function it_refuses_a_deactivated_user(): void
    {
        Queue::fake();

        User::factory()->create([
            'is_active' => false,
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

        $this->expectException(ValidationException::class);

        new SignInWithProvider(
            provider: SsoProviderEnum::GitHub,
            providerUser: $this->providerUser(),
        )->execute();
    }

    #[Test]
    public function it_keeps_two_accounts_apart_when_two_providers_hand_out_the_same_identifier(): void
    {
        Queue::fake();
        Notification::fake();

        $onGitHub = User::factory()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);
        $onGoogle = User::factory()->create([
            'sso_provider' => SsoProviderEnum::Google,
            'sso_id' => '108120124',
        ]);

        $signedIn = new SignInWithProvider(
            provider: SsoProviderEnum::Google,
            providerUser: $this->providerUser(),
        )->execute();

        $this->assertTrue($signedIn->is($onGoogle));
        $this->assertFalse($signedIn->is($onGitHub));
    }

    private function providerUser(
        string $id = '108120124',
        ?string $email = 'michael.scott@dundermifflin.com',
        ?string $name = 'Michael Scott',
    ): ProviderUser {
        return new ProviderUser()->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'nickname' => 'mscott',
        ]);
    }
}
