<?php

namespace Tests\Browser\Contacts;

use App\User;
use App\Contact;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class ContactsControllerTest extends DuskTestCase
{
    /**
     * Test if the user has 2fa Enable Link in Security Page.
     * @group multifa
     */
    public function test_it_displays_reminder_rules()
    {
        $user = factory(User::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $user->account->id]);

        $this->browse(function (Browser $browser) use ($user, $contact) {
            $browser->loginAs($user)
                    ->visit('people')
                    ->assertSee($contact->getCompleteName())
                    ->visit('people/'.$contact->id)
                    ->clickLink('click here')
                    ->acceptDialog()
                    ->assertDontSee($contact->getCompleteName());
        });
    }
}
