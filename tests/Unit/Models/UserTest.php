<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\SsoProviderEnum;
use App\Models\Account;
use App\Models\Passkey;
use App\Models\ProviderConnection;
use App\Models\SecurityKey;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_name_the_user_is_called_by(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals('Michael Scott', $user->name);
    }

    #[Test]
    public function it_gives_the_name_family_name_first_when_the_user_asked_for_it(): void
    {
        $user = User::factory()->familyNameFirst()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->assertEquals('Scott Michael', $user->name);
    }

    #[Test]
    public function it_knows_whether_it_has_a_photo(): void
    {
        $withPhoto = User::factory()->create(['photo_path' => 'photos/1/abc']);
        $withoutPhoto = User::factory()->create(['photo_path' => null]);

        $this->assertTrue($withPhoto->hasPhoto());
        $this->assertFalse($withoutPhoto->hasPhoto());
        $this->assertEquals('photos/1/abc_96.webp', $withPhoto->photoVariantPath(96));
        $this->assertEquals([96, 192], User::photoPixelSizes());
    }

    #[Test]
    public function it_hands_the_framework_an_empty_authentication_password_when_the_user_signs_in_through_sso(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertSame('', $user->getAuthPassword());
    }

    #[Test]
    public function it_knows_whether_it_uses_single_sign_on(): void
    {
        $withSso = User::factory()->singleSignOn()->create();
        $withPassword = User::factory()->create();

        $this->assertTrue($withSso->usesSingleSignOn());
        $this->assertFalse($withPassword->usesSingleSignOn());
    }

    #[Test]
    public function it_knows_whether_it_holds_a_passkey(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPasskeys());

        Passkey::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->hasPasskeys());
    }

    #[Test]
    public function it_uses_two_factor_authentication_with_the_app_a_security_key_or_both(): void
    {
        $neither = User::factory()->create();
        $app = User::factory()->twoFactor()->create();
        $key = User::factory()->create();
        SecurityKey::factory()->for($key)->create();
        $both = User::factory()->twoFactor()->create();
        SecurityKey::factory()->for($both)->create();

        $this->assertFalse($neither->usesTwoFactorAuthentication());
        $this->assertTrue($app->usesTwoFactorAuthentication());
        $this->assertTrue($key->usesTwoFactorAuthentication());
        $this->assertTrue($both->usesTwoFactorAuthentication());

        $this->assertTrue($app->usesAuthenticatorApp());
        $this->assertFalse($key->usesAuthenticatorApp());
    }

    #[Test]
    public function it_does_not_turn_two_factor_authentication_on_for_a_passkey(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->usesTwoFactorAuthentication());
    }

    #[Test]
    public function it_knows_a_passwordless_account_from_an_ordinary_one(): void
    {
        $this->assertFalse(User::factory()->create()->usesPasswordlessSignIn());
        $this->assertTrue(User::factory()->create(['password' => null])->usesPasswordlessSignIn());
    }

    #[Test]
    public function it_does_not_call_a_password_holder_passwordless_once_they_connect_a_provider(): void
    {
        $user = User::factory()->has(ProviderConnection::factory()->state(['provider' => SsoProviderEnum::GitHub, 'sso_id' => '108120124']))->create();

        $this->assertNotNull($user->password);
        $this->assertTrue($user->usesSingleSignOn());
        $this->assertFalse($user->usesPasswordlessSignIn());
    }

    #[Test]
    public function it_inspects_an_ability_and_keeps_the_reason_a_boolean_would_lose(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $user->inspect('create', Vault::class);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('Full access lifts the ceiling.', (string) $response->message());
        $this->assertFalse($user->can('create', Vault::class));
    }

    #[Test]
    public function it_inspects_an_ability_that_allows(): void
    {
        config(['monica.free_vault_limit' => 5]);

        $user = User::factory()->create();

        $response = $user->inspect('create', Vault::class);

        $this->assertTrue($response->allowed());
        $this->assertTrue($user->can('create', Vault::class));
    }

    #[Test]
    public function it_refuses_an_ability_the_user_does_not_hold(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Full access lifts the ceiling.');

        $user->authorize('create', Vault::class);
    }

    #[Test]
    public function it_asks_the_ability_about_this_user_rather_than_the_signed_in_one(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $atTheCeiling = User::factory()->create();
        Vault::factory()->create(['account_id' => $atTheCeiling->account_id]);

        $this->actingAs(User::factory()->create());

        $this->expectException(AuthorizationException::class);

        $atTheCeiling->authorize('create', Vault::class);
    }

    #[Test]
    public function it_offers_full_access_to_a_metered_account_without_it(): void
    {
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: Account::factory()->create());

        $this->assertTrue($user->isOfferedFullAccess());
    }

    #[Test]
    public function it_does_not_offer_full_access_once_the_user_hid_the_banner(): void
    {
        $user = User::factory()->create(['full_access_offer_dismissed_at' => now()]);

        $this->assertFalse($user->isOfferedFullAccess());
    }

    #[Test]
    public function it_does_not_offer_full_access_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $user = $this->assignUserToAccount(user: User::factory()->create(), account: Account::factory()->create());

        $this->assertFalse($user->isOfferedFullAccess());
    }

    #[Test]
    public function it_does_not_offer_full_access_to_an_account_that_holds_it(): void
    {
        $user = $this->assignUserToAccount(user: User::factory()->create(), account: Account::factory()->withGrantedAccess()->create());

        $this->assertFalse($user->isOfferedFullAccess());
    }

    #[Test]
    public function it_keeps_the_banner_hidden_after_the_account_loses_full_access(): void
    {
        $account = Account::factory()->withGrantedAccess()->create();
        $user = $this->assignUserToAccount(
            user: User::factory()->create(['full_access_offer_dismissed_at' => now()]),
            account: $account,
        );

        $account->update(['access_granted_at' => null]);

        $this->assertFalse($user->refresh()->isOfferedFullAccess());
    }
}
