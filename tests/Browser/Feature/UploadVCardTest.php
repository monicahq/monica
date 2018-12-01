<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

namespace Tests\BrowserFeature;

use Tests\DuskTestCase;
use App\Models\User\User;
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
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

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
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

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
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

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
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

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
