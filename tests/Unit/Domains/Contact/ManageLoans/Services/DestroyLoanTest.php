<?php

namespace Tests\Unit\Domains\Contact\ManageLoans\Services;

use App\Domains\Contact\ManageLoans\Services\DestroyLoan;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Loan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyLoanTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_loan(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);
        ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'feedable_id' => $loan->id,
            'feedable_type' => 'App\Models\Loan',
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyLoan)->execute($request);
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
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $loan);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan);
    }

    /** @test */
    public function it_fails_if_loan_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $loan);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Loan $loan): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'loan_id' => $loan->id,
        ];

        (new DestroyLoan)->execute($request);

        $this->assertDatabaseMissing('loans', [
            'id' => $loan->id,
        ]);
    }
}
