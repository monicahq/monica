<?php

namespace Tests\Unit\Domains\Contact\ManageMoodTrackingEvents\Services;

use App\Domains\Contact\ManageMoodTrackingEvents\Services\UpdateMoodTrackingEvent;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\ContactInformationType;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateMoodTrackingEventTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_mood_tracking_event(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create(['vault_id' => $vault->id]);
        $event = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $event, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateMoodTrackingEvent)->execute($request);
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
        $moodTrackingParameter = MoodTrackingParameter::factory()->create(['vault_id' => $vault->id]);
        $event = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $event, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $moodTrackingParameter = MoodTrackingParameter::factory()->create(['vault_id' => $vault->id]);
        $event = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $event, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create(['vault_id' => $vault->id]);
        $event = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $event, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_event_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $event = ContactInformationType::factory()->create();
        $moodTrackingParameter = MoodTrackingParameter::factory()->create(['vault_id' => $vault->id]);
        $event = MoodTrackingEvent::factory()->create([
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $event, $moodTrackingParameter);
    }

    /** @test */
    public function it_fails_if_parameter_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $moodTrackingParameter = MoodTrackingParameter::factory()->create();
        $event = MoodTrackingEvent::factory()->create([
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $event, $moodTrackingParameter);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, MoodTrackingEvent $event, MoodTrackingParameter $moodTrackingParameter): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'mood_tracking_event_id' => $event->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'rated_at' => '1982-02-03',
            'note' => 'fou',
            'number_of_hours_slept' => 3,
        ];

        $event = (new UpdateMoodTrackingEvent)->execute($request);

        $this->assertDatabaseHas('mood_tracking_events', [
            'id' => $event->id,
            'contact_id' => $contact->id,
            'mood_tracking_parameter_id' => $moodTrackingParameter->id,
            'rated_at' => '1982-02-03 00:00:00',
            'note' => 'fou',
            'number_of_hours_slept' => 3,
        ]);

        $this->assertDatabaseHas('contact_feed_items', [
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_MOOD_TRACKING_EVENT_UPDATED,
        ]);
    }
}
