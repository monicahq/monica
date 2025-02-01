<?php

namespace Tests\Unit\Domains\Contact\ManageQuickFacts\Services;

use App\Domains\Contact\ManageQuickFacts\Services\UpdateQuickFact;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\QuickFact;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateQuickFactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_quick_fact(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vaultTemplate = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $quickFact = QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => $vaultTemplate->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $quickFact, $vaultTemplate);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateQuickFact)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vaultTemplate = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $quickFact = QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => $vaultTemplate->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $quickFact, $vaultTemplate);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vaultTemplate = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $quickFact = QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => $vaultTemplate->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $quickFact, $vaultTemplate);
    }

    /** @test */
    public function it_fails_if_contact_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $vaultTemplate = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $quickFact = QuickFact::factory()->create([
            'contact_id' => $contact->id,
            'vault_quick_facts_template_id' => $vaultTemplate->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $quickFact, $vaultTemplate);
    }

    /** @test */
    public function it_fails_if_quick_fact_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $vaultTemplate = VaultQuickFactsTemplate::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $quickFact = QuickFact::factory()->create([
            'vault_quick_facts_template_id' => $vaultTemplate->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $quickFact, $vaultTemplate);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, QuickFact $quickFact, VaultQuickFactsTemplate $vaultTemplate): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'quick_fact_id' => $quickFact->id,
            'content' => 'love',
        ];

        $quickFact = (new UpdateQuickFact)->execute($request);

        $this->assertDatabaseHas('quick_facts', [
            'id' => $quickFact->id,
            'vault_quick_facts_template_id' => $vaultTemplate->id,
            'content' => 'love',
        ]);
    }
}
