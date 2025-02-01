<?php

namespace Tests\Unit\Domains\Contact\ManageLifeEvents\Services;

use App\Domains\Contact\ManageLifeEvents\Services\ToggleLifeEvent;
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

class ToggleLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ToggleLifeEvent)->execute($request);
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

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $account, $vault, $contact, $lifeEvent, $timelineEvent);
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

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEvent, $timelineEvent);
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

        $this->executeService($user, $user->account, $vault, $contact, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_timeline_event_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $timelineEvent = TimelineEvent::factory()->create();
        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $contact, $lifeEvent, $timelineEvent);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, LifeEvent $lifeEvent, TimelineEvent $timelineEvent): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'timeline_event_id' => $timelineEvent->id,
            'life_event_id' => $lifeEvent->id,
        ];

        $lifeEvent = (new ToggleLifeEvent)->execute($request);

        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'timeline_event_id' => $timelineEvent->id,
            'collapsed' => true,
        ]);
    }
}
