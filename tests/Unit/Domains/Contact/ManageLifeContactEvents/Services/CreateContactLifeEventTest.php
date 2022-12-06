<?php

namespace Tests\Unit\Domains\Contact\ManageLifeContactEvents\Services;

use App\Domains\Contact\ManageLifeContactEvents\Services\CreateContactLifeEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateContactLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_contact_life_event(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, 'test');
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateContactLifeEvent())->execute($request);
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
        $lifeEventCategory = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $account, $vault, $contact, $lifeEventType, 'test');
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
        $lifeEventCategory = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, 'test');
    }

    /** @test */
    public function it_fails_if_user_isnt_vault_editor(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, 'test');
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $lifeEventCategory = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, 'test');
    }

    /** @test */
    public function it_fails_if_life_event_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, 'test');
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, LifeEventType $life_event_type, string $summary): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $life_event_type->id,
            'summary' => $summary,
            'started_at' => '2022-01-01',
            'ended_at' => '2022-12-31',
        ];

        $lifeEvent = (new CreateContactLifeEvent())->execute($request);

        $this->assertDatabaseHas('contact_life_events', [
            'id' => $lifeEvent->id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $life_event_type->id,
            'summary' => $summary,
        ]);
    }
}
