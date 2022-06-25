<?php

namespace Tests\Unit\Domains\Settings\CreateAccount\Services;

use App\Jobs\CreateAuditLog;
use App\Jobs\SetupAccount;
use App\Models\User;
use App\Settings\CreateAccount\Services\CreateAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_account(): void
    {
        $this->executeService();
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateAccount())->execute($request);
    }

    private function executeService(): void
    {
        Queue::fake();

        $request = [
            'first_name' => 'john',
            'last_name' => 'john',
            'email' => 'john@email.com',
            'password' => 'john',
        ];

        $user = (new CreateAccount())->execute($request);

        $this->assertDatabaseHas('accounts', [
            'id' => $user->account->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $user->account_id,
            'first_name' => 'john',
            'last_name' => 'john',
            'email' => 'john@email.com',
            'is_account_administrator' => true,
            'timezone' => 'UTC',
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );

        Queue::assertPushed(SetupAccount::class, function ($job) use ($user) {
            return $job->user === $user && $job->onQueue('high');
        });

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'account_created';
        });
    }
}
