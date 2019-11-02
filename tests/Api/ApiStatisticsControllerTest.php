<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiStatisticsControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'instance_creation_date',
        'number_of_contacts',
        'number_of_users',
        'number_of_activities',
        'number_of_reminders',
        'number_of_new_users_last_week',
    ];

    public function test_it_gets_the_right_structure_of_the_public_statistics()
    {
        config(['monica.allow_statistics_through_public_api_access' => true]);

        $user = $this->signin();

        $response = $this->json('GET', '/api/statistics');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            $this->jsonStructure,
        ]);
    }

    public function test_it_returns_an_error_if_public_statistics_are_not_available()
    {
        config(['monica.allow_statistics_through_public_api_access' => false]);

        $user = $this->signin();

        $response = $this->json('GET', '/api/statistics');

        $this->expectNotFound($response);
    }
}
