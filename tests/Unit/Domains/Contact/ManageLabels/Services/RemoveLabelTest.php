<?php

namespace Tests\Unit\Domains\Contact\ManageLabels\Services;

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
use App\Contact\ManageLabels\Services\RemoveLabel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RemoveLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_label(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $label = Label::factory()->create(['vault_id' => $vault->id]);
        $contact->labels()->syncWithoutDetaching($label);

        $this->executeService($regis, $regis->account, $vault, $contact, $label);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveLabel)->execute($request);
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
        $contact->labels()->syncWithoutDetaching($label);

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
        $contact->labels()->syncWithoutDetaching($label);

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
        $contact->labels()->syncWithoutDetaching($label);

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
        $contact->labels()->syncWithoutDetaching($label);

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

        (new RemoveLabel)->execute($request);

        $this->assertDatabaseMissing('contact_label', [
            'contact_id' => $contact->id,
            'label_id' => $label->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'label_removed';
        });

        Queue::assertPushed(CreateContactLog::class, function ($job) {
            return $job->contactLog['action_name'] === 'label_removed';
        });
    }
}
