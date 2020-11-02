<?php

namespace Tests\Unit\Controllers\Contact;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LifeEventsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_list_of_life_events_for_the_contact()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get("/people/{$contact->hashID()}/lifeevents");

        $response->assertStatus(200);
    }
}
