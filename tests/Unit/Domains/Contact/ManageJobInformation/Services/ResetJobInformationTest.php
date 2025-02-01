<?php

namespace Tests\Unit\Domains\Contact\ManageJobInformation\Services;

use App\Domains\Contact\ManageJobInformation\Services\ResetJobInformation;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResetJobInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_resets_the_job_information(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
            'job_position' => 'tester',
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ResetJobInformation)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
            'job_position' => 'tester',
        ]);

        $this->executeService($regis, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'job_position' => 'tester',
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $company = Company::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
            'job_position' => 'tester',
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];

        (new ResetJobInformation)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'company_id' => null,
            'job_position' => null,
        ]);
    }
}
