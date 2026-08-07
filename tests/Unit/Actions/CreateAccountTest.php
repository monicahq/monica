<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccount;
use App\Enums\PlanEnum;
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
            'plan' => PlanEnum::Free->value,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $this->assertTrue(Hash::check('thatswhatshesaid', $user->password_hash));
        $this->assertEquals(1, $user->account->users()->count());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::AccountCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_starts_the_account_on_a_trial(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
        )->execute();

        $this->assertTrue($user->account->isOnTrial());
    }

    #[Test]
    public function it_accepts_a_plan(): void
    {
        Queue::fake();

        $user = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael.scott@dundermifflin.com',
            password: 'thatswhatshesaid',
            plan: PlanEnum::Business,
        )->execute();

        $this->assertEquals(PlanEnum::Business, $user->account->plan);
    }
}
