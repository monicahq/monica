<?php

namespace Tests\Unit\Services\Contact\ManageContactImportantDate;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use App\Models\ContactImportantDate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\ManageContactImportantDate\CreateContactImportantDate;

class CreateContactImportantDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_contact_date(): void
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
        (new CreateContactImportantDate)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
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
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => ContactImportantDate::TYPE_BIRTHDATE,
        ];

        $date = (new CreateContactImportantDate)->execute($request);

        $this->assertDatabaseHas('contact_important_dates', [
            'contact_id' => $contact->id,
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => 'birthdate',
        ]);

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => 'important_date_created',
        ]);

        $this->assertInstanceOf(
            ContactImportantDate::class,
            $date
        );

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'contact_date_created';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'contact_date_created';
        });
    }
}
