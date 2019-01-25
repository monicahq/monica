<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiMiscTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCountries = [
            'id',
            'iso',
            'name',
            'object',
    ];

    public function test_misc_get()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCountries],
        ]);
        $response->assertJsonFragment([
            'DEU' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Germany',
                'object' => 'country',
            ],
        ]);
    }

    public function test_misc_get_fr()
    {
        $user = $this->signin();
        $user->locale = 'fr';
        $user->save();

        $response = $this->json('GET', '/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCountries],
        ]);
        $response->assertJsonFragment([
            'DEU' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Allemagne',
                'object' => 'country',
            ],
        ]);
    }
}
