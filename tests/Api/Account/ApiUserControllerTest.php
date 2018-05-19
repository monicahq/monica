<?php

namespace Tests\Api\Contact;

use App\User;
use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiUserControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureUser = [
        'id',
        'object',
        'first_name',
        'last_name',
        'email',
        'timezone',
        'currency',
        'locale',
        'is_policy_compliant',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_the_authenticated_user()
    {
        $user = $this->signIn();

        $response = $this->get('/api/me');

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureUser,
        ]);

        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'object' => 'user',
        ]);
    }
}
