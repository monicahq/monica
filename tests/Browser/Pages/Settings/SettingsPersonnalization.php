<?php

namespace Tests\Browser\Pages\Settings;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsPersonnalization extends Page
{
    use DatabaseTransactions;

    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/settings/personalization';
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
            '@reminder-rule-label' => '.reminder-rule-7 > span',
        ];
    }
}
