<?php

namespace Tests;

use Tests\Traits\ApiSignIn;
use Illuminate\Foundation\Testing\TestResponse;

class ApiTestCase extends TestCase
{
    use ApiSignIn;

    /**
     * Test that the response contains a not found notification.
     *
     * @param  Response $response
     * @return mixed
     */
    public function expectNotFound(TestResponse $response)
    {
        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }
}
