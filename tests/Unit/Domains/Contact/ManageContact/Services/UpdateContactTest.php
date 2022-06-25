<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Services;

use App\Contact\ManageContact\Services\UpdateContact;
use App\Exceptions\NotEnoughPermissionException;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContact())->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = $this->createAccount();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $otherVault = Vault::factory()->create();

        $this->executeService($regis, $regis->account, $otherVault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'first_name' => 'Ross',
        ];

        $contact = (new UpdateContact())->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'vault_id' => $vault->id,
            'first_name' => 'Ross',
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'contact_updated';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'contact_updated';
        });
    }
}
