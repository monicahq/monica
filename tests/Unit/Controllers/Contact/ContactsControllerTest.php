<?php

namespace Tests\Unit\Controllers\Contact;

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

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'is_active' => false,
        ]);

        factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $this->assertTrue(AccountHelper::hasReachedContactLimit($user->account));
        $this->assertTrue(AccountHelper::hasLimitations($user->account));

        $response = $this->put("/people/{$contact->hashID()}/archive");

        $response->assertStatus(402);
    }
}
