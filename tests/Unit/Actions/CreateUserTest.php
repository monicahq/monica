<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateUser;
use App\Enums\SsoProviderEnum;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_user_in_the_account(): void
    {
        $account = Account::factory()->create();

        $user = new CreateUser(
            account: $account,
            firstName: 'Jim',
            lastName: 'Halpert',
            email: 'jim.halpert@dundermifflin.com',
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
            'email' => 'jim.halpert@dundermifflin.com',
            'is_active' => true,
        ]);
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->password));

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->password_changed_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_creates_a_user_without_a_password_when_they_sign_in_through_sso(): void
    {
        $account = Account::factory()->create();

        $user = new CreateUser(
            account: $account,
            firstName: 'Pam',
            lastName: 'Beesly',
            email: 'pam.beesly@dundermifflin.com',
            ssoProvider: SsoProviderEnum::Google,
            ssoId: '108120124',
        )->execute();

        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertEquals(SsoProviderEnum::Google, $user->sso_provider);
        $this->assertEquals('108120124', $user->sso_id);
        $this->assertTrue($user->usesSingleSignOn());
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function it_stores_the_time_zone_the_user_signed_up_from(): void
    {
        $account = Account::factory()->create();

        $user = new CreateUser(
            account: $account,
            firstName: 'Dwight',
            lastName: 'Schrute',
            email: 'dwight.schrute@dundermifflin.com',
            password: 'beetsbearsbattlestar',
            timezone: 'America/New_York',
        )->execute();

        $this->assertEquals('America/New_York', $user->timezone);
    }

    #[Test]
    public function it_falls_back_to_the_time_zone_of_the_instance_when_the_browser_did_not_say(): void
    {
        $account = Account::factory()->create();

        $user = new CreateUser(
            account: $account,
            firstName: 'Dwight',
            lastName: 'Schrute',
            email: 'dwight.schrute@dundermifflin.com',
            password: 'beetsbearsbattlestar',
        )->execute();

        $this->assertEquals(config('app.timezone'), $user->timezone);
    }
}
