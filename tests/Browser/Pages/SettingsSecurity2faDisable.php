<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class SettingsSecurity2faDisable extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/settings/security/2fa-disable';
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array
     */
    public function elements()
    {
        return [
            'verify' => "button[name='verify']",
            'otp' => '#one_time_password',
        ];
    }
}
