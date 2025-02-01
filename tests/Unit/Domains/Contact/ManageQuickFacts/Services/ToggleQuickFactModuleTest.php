<?php

namespace Tests\Unit\Domains\Contact\ManageQuickFacts\Services;

use App\Domains\Contact\ManageQuickFacts\Services\ToggleQuickFactModule;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ToggleQuickFactModuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_toggles_the_module(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($user, $user->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ToggleQuickFactModule)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($user, $account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->executeService($user, $user->account, $vault, $contact);
    }

    /** @test */
    public function it_fails_if_user_isnt_vault_editor(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
        ];

        (new ToggleQuickFactModule)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'show_quick_facts' => true,
        ]);

        (new ToggleQuickFactModule)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'show_quick_facts' => false,
        ]);
    }
}
