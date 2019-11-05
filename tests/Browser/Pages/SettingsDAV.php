<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsDAV extends Page
{
    use DatabaseTransactions;

    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/settings/dav';
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
            'dav_url_base' => '#dav_url_base',
        ];
    }
}
