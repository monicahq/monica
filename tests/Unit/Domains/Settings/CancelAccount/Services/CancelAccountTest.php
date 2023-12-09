<?php

namespace Tests\Unit\Domains\Settings\CancelAccount\Services;

use App\Domains\Settings\CancelAccount\Services\CancelAccount;
use App\Models\Account;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CancelAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_an_account(): void
    {
        $user = $this->createAdministrator();
        $vault = $this->createVault($user->account);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $file = File::factory()->create([
            'vault_id' => $contact->vault_id,
        ]);

        $this->executeService($user->account, $user, $file);
    }

    /** @test */
    public function it_queues_destroying_an_account(): void
    {
        Queue::fake();

        $user = $this->createAdministrator();

        $request = [
            'account_id' => $user->account->id,
            'author_id' => $user->id,
        ];

        CancelAccount::dispatch($request);

        Queue::assertPushed(CancelAccount::class, fn ($job) => $job->data === $request);
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
        CancelAccount::dispatch($request);
    }

    private function executeService(Account $account, User $user, ?File $file = null): void
    {
        Event::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
        ];

        CancelAccount::dispatchSync($request);

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);
    }
}
