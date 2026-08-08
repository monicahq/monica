<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccount;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_account_and_its_first_user(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('accounts', [
            'id' => $user->account_id,
            'is_self_hosted' => false,
            'has_lifetime_access' => false,
            'storage_used_in_bytes' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertTrue(Hash::check('thatswhatshesaid', $user->password));
        $this->assertTrue($user->is($user->account->user));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_gives_the_account_the_storage_of_the_free_offer(): void
    {
        Queue::fake();
        config(['monica.free_storage_limit_in_bytes' => 4096]);

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertSame(4096, $user->account->storage_limit_in_bytes);
        $this->assertTrue($user->account->isMetered());
    }

    #[Test]
    public function it_never_meters_an_account_on_a_self_hosted_instance(): void
    {
        Queue::fake();
        config(['monica.self_hosted' => true]);

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertTrue($user->account->is_self_hosted);
        $this->assertNull($user->account->storage_limit_in_bytes);
        $this->assertFalse($user->account->isMetered());
    }
}
