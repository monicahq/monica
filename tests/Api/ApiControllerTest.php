<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_http_status_code_returns_the_status_code()
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

    /** @test */
    public function get_error_code_returns_the_error_code()
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

    /** @test */
    public function get_with_parameter_returns_the_parameter()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            null,
            $apiController->getWithParameter()
        );

        $apiController->setWithParameter('test');

        $this->assertEquals(
            'test',
            $apiController->getWithParameter()
        );
    }

    /** @test */
    public function get_limit_per_page_code_returns_the_limit_per_page()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            0,
            $apiController->getLimitPerPage()
        );

        $apiController->setLimitPerPage(30);

        $this->assertEquals(
            30,
            $apiController->getLimitPerPage()
        );
    }

    /** @test */
    public function it_gets_the_sort_criteria()
    {
        $apiController = new ApiController;

        $this->assertEquals(
            'created_at',
            $apiController->getSortCriteria()
        );

        $apiController->setSortCriteria('created_at');

        $this->assertEquals(
            'created_at',
            $apiController->getSortCriteria()
        );
    }

    /** @test */
    public function it_only_accepts_some_sorting_parameters()
    {
        $apiController = new ApiController;

        $apiController->setSortCriteria('created_at');

        $this->assertEquals(
            'created_at',
            $apiController->getSortCriteria()
        );

        $apiController->setSortCriteria('anything');

        $this->assertEquals(
            '',
            $apiController->getSortCriteria()
        );
    }

    /** @test */
    public function calling_api_with_insane_limit_number_raises_an_error()
    {
        $user = $this->signin();

        $insaneLimit = config('api.max_limit_per_page') * 100;

        $response = $this->json('GET', "/api/contacts?limit={$insaneLimit}");

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => 'The limit parameter is too big',
            'error_code' => 30,
        ]);
    }

    /** @test */
    public function calling_api_with_a_wrong_sort_parameter_raises_an_error()
    {
        $user = $this->signin();

        $criteria = 'anything';

        $response = $this->json('GET', "/api/contacts?sort={$criteria}");

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => 'The sorting criteria is invalid',
            'error_code' => 39,
        ]);
    }

    /** @test */
    public function it_sets_the_order_by_parameters()
    {
        $apiController = new ApiController;

        $apiController->setSortCriteria('created_at');

        $this->assertEquals(
            'created_at',
            $apiController->getSortCriteria()
        );

        $this->assertEquals(
            'asc',
            $apiController->getSortDirection()
        );

        $apiController->setSortCriteria('-created_at');

        $this->assertEquals(
            'created_at',
            $apiController->getSortCriteria()
        );

        $this->assertEquals(
            'desc',
            $apiController->getSortDirection()
        );
    }

    /** @test */
    public function root_api()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'success' => [
                'message' => 'Welcome to Monica',
            ],
        ]);
        $response->assertJsonFragment([
            'contacts_url' => route('api.contacts'),
        ]);
    }
}
