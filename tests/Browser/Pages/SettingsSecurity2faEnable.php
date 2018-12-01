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



namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsSecurity2faEnable extends Page
{
    use DatabaseTransactions;

    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return '/settings/security/2fa-enable';
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
            'barcode' => '#barcode',
            'secretkey' => '#secretkey',
            'verify' => "button[name='verify']",
            'otp' => '#one_time_password',
        ];
    }
}
