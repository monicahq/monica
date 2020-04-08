<?php

namespace Tests\Feature\Authentication;

use Tests\FeatureTestCase;

class AuthenticateTest extends FeatureTestCase
{
    public function test_guest_is_redirect_to_login()
    {
        $response = $this->get('/people');

        $response->assertStatus(302);
        $response->assertRedirect('/');
    }
}
