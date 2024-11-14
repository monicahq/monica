<?php

namespace Tests\Unit\Domains\Contact\ManageMoodTrackingEvents\Services;

use App\Domains\Contact\ManageMoodTrackingEvents\Services\DestroyMoodTrackingEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\MoodTrackingEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyMoodTrackingEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_mood_tracking_event(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $moodTrackingEvent);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyMoodTrackingEvent)->execute($request);
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
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $moodTrackingEvent);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $moodTrackingEvent);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingEvent = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $moodTrackingEvent);
    }

    /** @test */
    public function it_fails_if_event_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingEvent = MoodTrackingEvent::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $moodTrackingEvent);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, MoodTrackingEvent $moodTrackingEvent): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'mood_tracking_event_id' => $moodTrackingEvent->id,
        ];

        (new DestroyMoodTrackingEvent)->execute($request);

        $this->assertDatabaseMissing('mood_tracking_events', [
            'id' => $moodTrackingEvent->id,
        ]);

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_MOOD_TRACKING_EVENT_DESTROYED,
        ]);
    }
}
