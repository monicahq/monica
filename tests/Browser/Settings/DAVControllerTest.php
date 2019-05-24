<?php

namespace Tests\Browser\Settings;

use Tests\DuskTestCase;
use App\Models\User\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\SettingsDAV;

class DAVControllerTest extends DuskTestCase
{
    /**
     * Test if the dav url is present.
     */
    public function test_it_has_dav_url()
    {
        $user = factory(User::class)->create();
        $user->account->populateDefaultFields();
        $user->acceptPolicy();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new SettingsDAV)
                    ->assertVisible('dav_url_base')
                    ->assertSourceHas(config('app.url').'/dav');
        });
    }
}
