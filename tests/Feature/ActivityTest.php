<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_display_activities_for_a_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $activity1 = factory(Activity::class)->create([
            'date_it_happened' => '2015-10-29 10:10:10',
            'account_id' => $contact->account_id,
            'description' => 'that happen',
        ]);
        $contact->activities()->attach($activity1, ['account_id' => $contact->account_id]);

        $response = $this->get("/people/{$contact->hashID()}/activities/2015");

        $response->assertStatus(200);
        $response->assertSee('that happen');
    }
}
