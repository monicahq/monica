<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NameOrderEnum;
use App\Enums\SsoProviderEnum;
use App\Models\Account;
use App\Models\Passkey;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_the_only_user_of_its_account(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);

        $this->expectException(QueryException::class);

        User::factory()->create(['account_id' => $account->id]);
    }

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
    public function it_authenticates_against_the_password_column(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dwightschrute')]);

        $this->assertEquals($user->password, $user->getAuthPassword());
        $this->assertTrue(Hash::check('dwightschrute', $user->getAuthPassword()));
    }

    #[Test]
    public function it_has_no_authentication_password_when_the_user_signs_in_through_sso(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->getAuthPassword());
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
    public function it_knows_which_provider_it_signs_in_with(): void
    {
        $withSso = User::factory()->singleSignOn()->create();
        $withPassword = User::factory()->create();

        $this->assertTrue($withSso->signsInWith(SsoProviderEnum::Google));
        $this->assertFalse($withSso->signsInWith(SsoProviderEnum::GitHub));
        $this->assertFalse($withPassword->signsInWith(SsoProviderEnum::Google));
    }

    #[Test]
    public function it_hides_the_password_when_serialized(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    #[Test]
    public function it_falls_back_to_the_time_zone_of_the_instance_and_to_the_given_name_first(): void
    {
        $user = User::factory()->create();

        $user->refresh();

        $this->assertEquals('UTC', $user->timezone);
        $this->assertEquals(NameOrderEnum::GivenFirst, $user->name_order);
        $this->assertNull($user->date_format);
        $this->assertNull($user->number_format);
    }

    #[Test]
    public function it_has_no_password_change_date_when_the_password_was_never_set(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->password_changed_at);
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted($user);
    }

    #[Test]
    public function it_knows_whether_the_email_address_was_confirmed(): void
    {
        $this->assertFalse(User::factory()->unverified()->create()->hasVerifiedEmail());
        $this->assertTrue(User::factory()->create()->hasVerifiedEmail());
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
    public function it_knows_a_passwordless_account_from_an_ordinary_one(): void
    {
        $this->assertFalse(User::factory()->create()->usesPasswordlessSignIn());
        $this->assertTrue(User::factory()->create(['password' => null])->usesPasswordlessSignIn());
    }

    #[Test]
    public function it_calls_an_account_created_through_a_provider_passwordless(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->password);
        $this->assertTrue($user->usesSingleSignOn());
        $this->assertTrue($user->usesPasswordlessSignIn());
    }

    #[Test]
    public function it_does_not_call_a_password_holder_passwordless_once_they_connect_a_provider(): void
    {
        $user = User::factory()->create([
            'sso_provider' => SsoProviderEnum::GitHub,
            'sso_id' => '108120124',
        ]);

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
    public function it_lets_an_ability_the_user_holds_through(): void
    {
        config(['monica.free_vault_limit' => 5]);

        $user = User::factory()->create();

        $this->assertTrue($user->authorize('create', Vault::class)->allowed());
        $this->assertTrue($user->can('create', Vault::class));
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
}
