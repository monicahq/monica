<?php

namespace Tests\Unit\Domains\Contact\ManageLifeEvents\Services;

use App\Domains\Contact\ManageLifeEvents\Services\CreateLifeEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_life_event(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateLifeEvent)->execute($request);
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
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $account, $vault, $contact, $lifeEventType, $timelineEvent);
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
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
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
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_timeline_event_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $timelineEvent = TimelineEvent::factory()->create([]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create(['vault_id' => $vault->id]);
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_life_event_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create(['life_event_category_id' => $lifeEventCategory->id]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEventType, $timelineEvent);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, LifeEventType $lifeEventType, TimelineEvent $timelineEvent): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'timeline_event_id' => $timelineEvent->id,
            'life_event_type_id' => $lifeEventType->id,
            'summary' => null,
            'description' => null,
            'happened_at' => '1982-02-04',
            'costs' => null,
            'currency_id' => null,
            'paid_by_contact_id' => null,
            'duration_in_minutes' => null,
            'distance' => null,
            'distance_unit' => null,
            'from_place' => null,
            'to_place' => null,
            'place' => null,
            'participant_ids' => [$contact->id],
        ];

        $lifeEvent = (new CreateLifeEvent)->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'timeline_event_id' => $timelineEvent->id,
            'life_event_type_id' => $lifeEventType->id,
            'summary' => null,
            'happened_at' => '1982-02-04 00:00:00',
        ]);

        $this->assertDatabaseHas('life_event_participants', [
            'life_event_id' => $lifeEvent->id,
            'contact_id' => $contact->id,
        ]);
    }
}
