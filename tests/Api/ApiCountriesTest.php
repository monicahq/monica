<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiCountriesTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCountries = [
        'id',
        'iso',
        'name',
        'object',
    ];

    /** @test */
    public function it_gets_the_list_of_countries()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCountries],
        ]);
        $response->assertJsonFragment([
            'de' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Germany',
                'object' => 'country',
            ],
        ]);
    }

    /** @test */
    public function it_gets_a_specific_country_in_a_specific_locale()
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
            'de' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Allemagne',
                'object' => 'country',
            ],
        ]);
    }
}
