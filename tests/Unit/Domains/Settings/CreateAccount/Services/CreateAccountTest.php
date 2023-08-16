<?php

namespace Tests\Unit\Domains\Settings\CreateAccount\Services;

use App\Domains\Settings\CreateAccount\Jobs\SetupAccount;
use App\Domains\Settings\CreateAccount\Services\CreateAccount;
use App\Models\User;
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
        app(CreateAccount::class)->execute($request);
    }

    private function executeService(): void
    {
        Queue::fake();
        config(['monica.default_storage_limit_in_mb' => 120]);

        $request = [
            'first_name' => 'john',
            'last_name' => 'john',
            'email' => 'john@email.com',
            'password' => 'john',
        ];

        $user = app(CreateAccount::class)->execute($request);

        $this->assertDatabaseHas('accounts', [
            'id' => $user->account->id,
            'storage_limit_in_mb' => 120,
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

        Queue::assertPushed(SetupAccount::class, fn ($job) => $job->data['author_id'] === $user->id);
        Queue::assertPushedOn('high', SetupAccount::class);
    }
}
