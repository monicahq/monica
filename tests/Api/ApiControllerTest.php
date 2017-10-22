<?php

namespace Tests\Api;

use Tests\TestCase;
use App\Http\Controllers\Api\ApiController;

class ApiControllerTest extends TestCase
{
    public function test_get_http_status_code_returns_the_status_code()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            200,
            $apiController->getHTTPStatusCode()
        );

        $apiController->setHTTPStatusCode(300);

        $this->assertEquals(
            300,
            $apiController->getHTTPStatusCode()
        );
    }

    public function test_get_error_code_returns_the_error_code()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            null,
            $apiController->getErrorCode()
        );

        $apiController->setErrorCode(30);

        $this->assertEquals(
            30,
            $apiController->getErrorCode()
        );
    }

    public function test_get_limit_per_page_code_returns_the_limit_per_page()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            10,
            $apiController->getLimitPerPage()
        );

        $apiController->setLimitPerPage(30);

        $this->assertEquals(
            30,
            $apiController->getLimitPerPage()
        );
    }
}
