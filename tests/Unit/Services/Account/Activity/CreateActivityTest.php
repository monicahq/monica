<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Account\ActivityType;
use App\Models\Instance\Emotion\Emotion;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\Activity\CreateActivity;

class CreateActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_an_activity_and_creates_an_entry_in_the_journal()
    {
        $account = factory(Account::class)->create();
        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $account->id,
        ]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => $contacts->map(function ($contact) {
                return $contact->id;
            })->toArray(),
        ];

        $activity = app(CreateActivity::class)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'account_id' => $account->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ]);

        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('activity_contact', [
                'account_id' => $account->id,
                'activity_id' => $activity->id,
                'contact_id' => $contact->id,
            ]);
        }

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $account->id,
            'journalable_id' => $activity->id,
            'journalable_type' => get_class($activity),
        ]);
    }

    /** @test */
    public function it_adds_emotions()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $emotion = factory(Emotion::class)->create([]);
        $emotion2 = factory(Emotion::class)->create([]);

        $emotionArray = [];
        array_push($emotionArray, $emotion->id);
        array_push($emotionArray, $emotion2->id);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'emotions' => $emotionArray,
            'contacts' => [$contact->id],
        ];

        $activity = app(CreateActivity::class)->execute($request);

        $this->assertDatabaseHas('emotion_activity', [
            'account_id' => $account->id,
            'activity_id' => $activity->id,
            'emotion_id' => $emotion->id,
        ]);

        $this->assertDatabaseHas('emotion_activity', [
            'account_id' => $account->id,
            'activity_id' => $activity->id,
            'emotion_id' => $emotion2->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        app(CreateActivity::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_activity_type_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => [$contact->id],
        ];

        $this->expectException(ModelNotFoundException::class);
        app(CreateActivity::class)->execute($request);
    }
}
