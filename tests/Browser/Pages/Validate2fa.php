<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class Validate2fa extends Page
{
    use DatabaseTransactions;

    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/validate2fa';
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
            '@element' => '#selector',
        ];
    }
}
