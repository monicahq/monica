<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsSecurity extends Page
{
    use DatabaseTransactions;

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
            'barcode' => '#barcode',
            'secretkey' => '#secretkey',
            'buttonVerify' => "button[name='verify']",
            'enableVerify' => '#verify1',
            'disableVerify' => '#verify2',
            'otpenable' => '#one_time_password1',
            'otpdisable' => '#one_time_password2',
            'enableModal' => '#enableModal',
            'disableModal' => '#disableModal',
            'registerModal' => '#registerModal',
        ];
    }
}
