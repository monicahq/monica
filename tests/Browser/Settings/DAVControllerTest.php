<?php

namespace Tests\Browser\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\SettingsDAV;
use Tests\DuskTestCase;

class DAVControllerTest extends DuskTestCase
{
    /**
     * Test if the dav url is present.
     */
    public function test_it_has_dav_url()
    {
        $this->browse(function (Browser $browser) {
            $browser->login()
                    ->visit(new SettingsDAV)
                    ->assertVisible('dav_url_base')
                    ->assertSourceHas(config('app.url').'/dav');
        });
    }
}
