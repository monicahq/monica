<?php

namespace Tests\BrowserFeature;

use App\User;
use Tests\DuskTestCase;
use Tests\Browser\Pages\ImportVCardUpload;

class UploadVCardTest extends DuskTestCase
{
    /**
     * Make sure that the Add contact view has the link to the upload screen,
     * and that the screen contains the blank view.
     *
     * @return void
     */
    public function test_upload_vcard_is_accessible_from_add_contact_view()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields($user->account);

        $this->browse(function ($browser) use ($user) {
            $browser->loginAs($user)
                  ->visit('/people/add')
                  ->assertSee('import your contacts');

            $browser->clickLink('import your contacts')
                    ->assertSee('You haven’t imported any contacts yet');
        });
    }

    /**
     * Make sure that the Import button leads to the Import screen, and that
     * the cancel button leads to the Blank import screen.
     *
     * @return void
     */
    public function test_import_button_leads_to_import_screen()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields($user->account);

        $this->browse(function ($browser) use ($user) {
            $browser->loginAs($user)
                  ->visit('/settings/import')
                  ->clickLink('Import vCard')
                  ->assertPathIs('/settings/import/upload')
                  ->clickLink('Cancel')
                  ->assertPathIs('/settings/import');
        });
    }

    /**
     * Upload a single contact from a valid vcard file.
     *
     * @return void
     */
    public function test_user_can_import_contacts_from_a_vcf_card()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields($user->account);

        $this->browse(function ($browser) use ($user) {
            $browser->loginAs($user)
                  ->visit('/settings/import')
                  ->clickLink('Import vCard')
                  ->attach('vcard', base_path('tests/stubs/single_vcard_stub.vcard'))
                  ->on(new ImportVCardUpload)
                  ->scrollTo('upload')
                  ->press('Upload')
                  ->assertSee('1 imported');
        });
    }

    /**
     * Upload a contact from a broken vCard and see that it triggers an error.
     *
     * @return void
     */
    public function test_user_see_error_when_importing_broken_vcard()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields($user->account);

        $this->browse(function ($browser) use ($user) {
            $browser->loginAs($user)
                  ->visit('/settings/import')
                  ->clickLink('Import vCard')
                  ->attach('vcard', base_path('tests/stubs/broken_vcard_stub.vcard'))
                  ->on(new ImportVCardUpload)
                  ->scrollTo('upload')
                  ->press('Upload')
                  ->assertSee('The vcard must be a file of type: vcf, vcard.');
        });
    }
}
