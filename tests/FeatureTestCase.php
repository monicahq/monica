<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestResponse;
use Tests\Traits\SignIn;

class FeatureTestCase extends TestCase
{
    use SignIn;

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
}
