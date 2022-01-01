<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Helpers\AccountHelper;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_cant_unarchive_contact_if_limited_account()
    {
        config(['monica.requires_subscription' => true]);
        $user = $this->signin();

        $contact = factory(Contact::class)->state('archived')->create([
            'account_id' => $user->account_id,
        ]);

        factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $this->assertTrue(AccountHelper::hasReachedContactLimit($user->account));
        $this->assertTrue(AccountHelper::hasLimitations($user->account));

        $response = $this->put("/people/{$contact->hashID()}/archive");

        $response->assertStatus(402);
    }

    /** @test */
    public function it_stays_in_touch()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->post("/people/{$contact->hashID()}/stayintouch", [
            'frequency' => 5,
            'state' => 1,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'stay_in_touch_frequency' => 5,
        ]);
    }
}
