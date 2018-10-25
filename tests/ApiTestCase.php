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
     * @param TestResponse $response
     */
    public function expectNotFound(TestResponse $response)
    {
        $response->assertStatus(404);

        $response->assertJson([
            'error' => [
                'message' => 'The resource has not been found',
                'error_code' => 31,
            ],
        ]);
    }

    /**
     * Test that the response contains a not found notification.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectDataError(TestResponse $response, $message = '')
    {
        $response->assertStatus(400);

        $response->assertJson([
            'error' => [
                'message' => $message,
                'error_code' => 32,
            ],
        ]);
    }

    /**
     * Test that the response contains a not found notification.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectInvalidParameter(TestResponse $response, $message = '')
    {
        $response->assertStatus(400);

        $response->assertJson([
            'error' => [
                'message' => $message,
                'error_code' => 41,
            ],
        ]);
    }
}
