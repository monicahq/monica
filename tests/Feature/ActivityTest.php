<?php

namespace Tests\Feature;

use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'summary',
        'description',
        'happened_at',
        'activity_type',
        'attendees' => [
            'total',
            'contacts',
        ],
        'emotions',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    protected $jsonStructureContacts = [
        'id',
        'name',
    ];

    private function createActivityAndAttachToContact(User $user, Contact $contact)
    {
        $activity = factory(Activity::class)->create([
            'account_id' => $user->account_id,
        ]);
        $activity->contacts()->syncWithoutDetaching([$contact->id => [
            'account_id' => $activity->account_id,
        ]]);
    }

    public function test_it_gets_the_list_of_activities()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $this->createActivityAndAttachToContact($user, $contact);
        $this->createActivityAndAttachToContact($user, $contact);
        $this->createActivityAndAttachToContact($user, $contact);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/activities');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructure,
            ],
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()['data']
        );
    }

    public function test_it_gets_the_list_of_contacts_to_associate_with_the_activity()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        // also create of other contacts in the account
        factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/activities/contacts/');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonStructureContacts,
        ]);

        $this->assertCount(
            3,
            $response->decodeResponseJson()
        );
    }
}
