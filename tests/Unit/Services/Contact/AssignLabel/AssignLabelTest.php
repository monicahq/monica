<?php

namespace Tests\Unit\Services\Contact\AssignLabel;

use Tests\TestCase;
use App\Models\User;
use App\Models\Label;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Jobs\CreateContactLog;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use App\Services\Contact\AssignLabel\AssignLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssignLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_assigns_a_label(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $label = Label::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $label);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new AssignLabel)->execute($request);
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
        $label = Label::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $account, $vault, $contact, $label);
    }

    /** @test */
    public function it_fails_if_label_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $label = Label::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $label);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $label = Label::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $label);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $label = Label::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($regis, $regis->account, $vault, $contact, $label);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Label $label): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'label_id' => $label->id,
            'contact_id' => $contact->id,
        ];

        (new AssignLabel)->execute($request);

        $this->assertDatabaseHas('contact_label', [
            'contact_id' => $contact->id,
            'label_id' => $label->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'label_assigned';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'label_assigned';
        });
    }
}
