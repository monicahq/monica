<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateUser;
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
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->password_hash));

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
            ssoProvider: 'google',
        )->execute();

        $this->assertNull($user->password_hash);
        $this->assertNull($user->password_changed_at);
        $this->assertEquals('google', $user->sso_provider);
        $this->assertTrue($user->usesSingleSignOn());
    }
}
