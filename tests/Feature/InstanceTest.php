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

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Check if, by default, the disable signups feature is turned off in an
     * instance.
     *
     * @return void
     */
    public function test_disable_signup_set_to_false_shows_signup_button()
    {
        config(['monica.disable_signup' => false]);

        $response = $this->get('/');

        $response->assertSee(
            'Sign up'
        );
    }

    /**
     * If an instance sets `disable_signup` env variable to true, it should hide
     * the signup button on the Sign in page.
     * Also, trying to reach `/register` should lead to a 403 page.
     *
     * @return void
     */
    public function test_disable_signup_set_to_true_hides_signup_button_and_register_page()
    {
        config(['monica.disable_signup' => true]);

        $response = $this->get('/');
        $response->assertDontSee(
            'Sign up'
        );
    }
}
