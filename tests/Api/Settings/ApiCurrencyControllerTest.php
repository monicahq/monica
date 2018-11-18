<?php

namespace Tests\Api\Settings;

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

    public function test_currency_get_all()
    {
        // in theory the currencies table is seeded by the initial script
        $response = $this->json('GET', '/api/currencies/');

        $response->assertStatus(200);

        $this->assertCount(
            15,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 156,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureCurrency],
        ]);
    }

    public function test_currency_get_one()
    {
        $currency = factory(Currency::class)->create([]);

        $response = $this->json('GET', '/api/currencies/'.$currency->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $currency->id,
            'object' => 'currency',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCurrency,
        ]);
    }

    public function test_currency_get_one_error()
    {
        $response = $this->json('GET', '/api/currencies/0');

        $this->expectNotFound($response);
    }
}
