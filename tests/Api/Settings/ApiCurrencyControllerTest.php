<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Settings\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiCurrencyControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureCurrency = [
        'id',
        'object',
        'iso',
        'name',
        'symbol',
    ];

    public function test_it_gets_a_list_of_currencies()
    {
        $currency = factory(Currency::class, 10)->create([]);

        $response = $this->json('GET', '/api/currencies/');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);

        $response->assertJsonFragment([
            'id' => $currency->id,
            'object' => 'currency',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCurrency,
        ]);
    }

    public function it_gets_a_single_currency()
    {
        $currency = factory(Currency::class)->create([]);

        $response = $this->json('GET', '/api/currencies/' . $currency->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $currency->id,
            'object' => 'currency',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCurrency,
        ]);
    }
}
