<?php

namespace Tests\Unit\Domains\Settings\CancelAccount\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Settings\CancelAccount\Services\CancelAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CancelAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_an_account(): void
    {
        $user = $this->createAdministrator();
        $this->executeService($user->account, $user);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $user = $this->createAdministrator();
        $account = Account::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($account, $user);
    }

    /** @test */
    public function it_fails_if_user_is_not_account_administrator(): void
    {
        $user = $this->createUser();
        $account = Account::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($account, $user);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CancelAccount)->execute($request);
    }

    private function executeService(Account $account, User $user): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
        ];

        (new CancelAccount)->execute($request);

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }
}
