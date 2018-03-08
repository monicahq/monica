<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class SettingsSecurity extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/settings/security';
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
            'two_factor_link' => "a:contains('Enable Two Factor Authentication')",
        ];
    }
}
