<?php

namespace Tests;

use Tests\Traits\SignIn;
use Illuminate\Foundation\Testing\TestResponse;

class FeatureTestCase extends TestCase
{
    use SignIn;

    /**
     * Test that the response contains an error.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectUnauthorizedError(TestResponse $response, $message = null)
    {
        $this->expectDataError($response, $message ?? trans('app.error_unauthorized'));
    }

    /**
     * Test that the response contains an error.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectDataError(TestResponse $response, $message = '')
    {
        $response->assertStatus(400);

        $response->assertJson([
            'message' => $message,
        ]);
    }
}
