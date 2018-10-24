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
     * @return mixed
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
     * @return mixed
     */
    public function expectDataError(TestResponse $response, array $message = [''])
    {
        $response->assertStatus(200);

        $response->assertJson([
            'error' => [
                'message' => $message,
                'error_code' => 32,
            ],
        ]);
    }
}
