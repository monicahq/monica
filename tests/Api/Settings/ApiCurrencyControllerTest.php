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

    /** @test */
    public function it_gets_all_the_currencies()
    {
        // in theory the currencies table is seeded by the initial script
        $response = $this->json('GET', '/api/currencies/');

        $response->assertStatus(200);

        $this->assertCount(
            15,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 153,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructureCurrency],
        ]);
    }

    /** @test */
    public function it_gets_one_currency()
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

    /** @test */
    public function it_gets_a_currency_that_is_invalid()
    {
        $response = $this->json('GET', '/api/currencies/0');

        $this->expectNotFound($response);
    }
}
