<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Account\Activity\Activity\UpdateActivity;

class UpdateActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_an_activity()
    {
        $activity = factory(Activity::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => [$contact->id],
        ];

        app(UpdateActivity::class)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'account_id' => $activity->account_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    /** @test */
    public function it_removes_old_associated_contacts()
    {
        $activity = factory(Activity::class)->create();
        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $activity->account_id,
        ]);
        foreach ($contacts as $contact) {
            $activity->contacts()->syncWithoutDetaching([$contact->id => [
                'account_id' => $activity->account_id,
            ]]);
        }

        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('activity_contact', [
                'account_id' => $activity->account_id,
                'activity_id' => $activity->id,
                'contact_id' => $contact->id,
            ]);
        }

        $newContact = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => [$newContact->id],
        ];

        app(UpdateActivity::class)->execute($request);

        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contact_id' => $newContact->id,
        ]);
        foreach ($contacts as $contact) {
            $this->assertDatabaseMissing('activity_contact', [
                'account_id' => $activity->account_id,
                'activity_id' => $activity->id,
                'contact_id' => $contact->id,
            ]);
        }
    }

    /** @test */
    public function it_removes_old_associated_contacts_and_keep_previous_one()
    {
        $activity = factory(Activity::class)->create();
        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $activity->account_id,
        ]);
        foreach ($contacts as $contact) {
            $activity->contacts()->syncWithoutDetaching([$contact->id => [
                'account_id' => $activity->account_id,
            ]]);
        }

        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('activity_contact', [
                'account_id' => $activity->account_id,
                'activity_id' => $activity->id,
                'contact_id' => $contact->id,
            ]);
        }

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => [$contacts[0]->id, $contacts[1]->id],
        ];

        app(UpdateActivity::class)->execute($request);

        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contact_id' => $contacts[0]->id,
        ]);
        $this->assertDatabaseHas('activity_contact', [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contact_id' => $contacts[1]->id,
        ]);
        $this->assertDatabaseMissing('activity_contact', [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
            'contact_id' => $contacts[2]->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateActivity::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $activity = factory(Activity::class)->create([]);
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $activity->account_id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_id' => $activity->id,
            'activity_type_id' => $activity->activity_type_id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
            'contacts' => [$contact->id],
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateActivity::class)->execute($request);
    }
}
