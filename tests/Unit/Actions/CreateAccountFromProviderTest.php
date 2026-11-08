<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccountFromProvider;
use App\Enums\SsoProviderEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAccountFromProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_account_and_the_user(): void
    {
        Queue::fake();
        Notification::fake();

        $user = $this->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);
        $this->assertDatabaseHas('provider_connections', ['user_id' => $user->id, 'provider' => 'github', 'sso_id' => '108120124']);
        $this->assertDatabaseHas('accounts', ['id' => $user->account_id]);
    }

    #[Test]
    public function it_gives_the_account_everything_registering_gives(): void
    {
        Queue::fake(LogUserAction::class);
        Notification::fake();

        $user = $this->create();

        $this->assertTrue($user->account->vaults()->exists());
        $this->assertTrue($user->account->valueLists()->exists());
        $this->assertTrue($user->account->relationshipTypes()->exists());
    }

    #[Test]
    public function it_leaves_the_account_without_a_password(): void
    {
        Queue::fake();
        Notification::fake();

        $user = $this->create();

        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->usesSingleSignOn());
    }

    #[Test]
    public function it_leaves_the_address_unconfirmed_and_asks_for_it_to_be_verified(): void
    {
        Queue::fake();
        Notification::fake();

        $user = $this->create();

        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    private function create(): User
    {
        return new CreateAccountFromProvider(
            provider: SsoProviderEnum::GitHub,
            ssoId: '108120124',
            email: 'michael.scott@dundermifflin.com',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();
    }
}
