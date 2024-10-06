<?php

namespace Tests\Unit\Domains\Contact\ManageLifeEvents\Services;

use App\Domains\Contact\ManageLifeEvents\Services\DestroyLifeEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeEvent;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyLifeEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_contact_life_event(): void
    {
        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'summary' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyLifeEvent)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $account, $vault, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_VIEW, $vault);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_timeline_event_does_not_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $timelineEvent = TimelineEvent::factory()->create([]);

        $lifeEvent = LifeEvent::factory()->create([
            'timeline_event_id' => $timelineEvent->id,
        ]);

        $this->executeService($user, $user->account, $vault, $lifeEvent, $timelineEvent);
    }

    /** @test */
    public function it_fails_if_life_events_doesn_belong_to_timeline_event(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVault($user->account);
        $vault = $this->setPermissionInVault($user, Vault::PERMISSION_EDIT, $vault);
        $timelineEvent = TimelineEvent::factory()->create(['vault_id' => $vault->id]);

        $lifeEvent = LifeEvent::factory()->create([]);

        $this->executeService($user, $user->account, $vault, $lifeEvent, $timelineEvent);
    }

    private function executeService(User $user, Account $account, Vault $vault, LifeEvent $lifeEvent, TimelineEvent $timelineEvent): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $user->id,
            'timeline_event_id' => $timelineEvent->id,
            'life_event_id' => $lifeEvent->id,
        ];

        (new DestroyLifeEvent)->execute($request);

        $this->assertDatabaseMissing('life_events', [
            'id' => $lifeEvent->id,
        ]);

        $this->assertDatabaseMissing('timeline_events', [
            'id' => $timelineEvent->id,
        ]);
    }
}
