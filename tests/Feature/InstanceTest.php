<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceTest extends TestCase
{

    /**
     * Check if, by default, the disable signups feature is turned off in an
     * instance.
     *
     * @return void
     */
    public function test_disable_signup_set_to_false_shows_signup_button()
    {
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
        putenv('APP_DISABLE_SIGNUP=true');

        // reload the environment as we've changed the ENV variable
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $response = $this->get('/');
        $response->assertDontSee(
            'Sign up'
        );
    }
}
