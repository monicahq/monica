<?php

namespace Tests;

class TestResponseMacros
{
    public function assertResourceNotFound()
    {
        return function () {
            $this->assertStatus(404);
            $this->assertExactJson([
                'error' => [
                    'error_code' => 31,
                    'message' => 'The resource has not been found',
                ],
            ]);
        };
    }
}
