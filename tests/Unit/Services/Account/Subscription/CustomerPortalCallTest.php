<?php

namespace Tests\Unit\Services\Account\Subscription;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Subscription\CustomerPortalCall;

class CustomerPortalCallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_validates_a_licence_key()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => '1']);

        Cache::flush();

        Http::fake([
            'https://fake.test/oauth/token' => Http::response(['access_token' => '123']),
            'https://fake.test/api/validate' => Http::response([], 200),
        ]);

        $request = [
            'licence_key' => 'key',
        ];

        $status = app(CustomerPortalCall::class)->execute($request);

        $this->assertEquals(200, $status);

        Http::assertSentInOrder([
            function ($request, $response) {
                $this->assertEquals('POST', $request->method());
                $this->assertEquals('https://fake.test/oauth/token', $request->url());
                $this->assertEquals('grant_type=client_credentials&client_id=1&client_secret=1&scope=manage-key', $request->body());
                $this->assertEquals('{"access_token":"123"}', $response->body());

                return true;
            },
            function ($request, $response) {
                $this->assertEquals('POST', $request->method());
                $this->assertEquals('https://fake.test/api/validate', $request->url());
                $this->assertEquals('{"licence_key":"key"}', $request->body());
                $this->assertEquals(['Bearer 123'], $request->header('Authorization'));
                $this->assertEquals(200, $response->status());

                return true;
            },
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_licence_key_does_not_exist()
    {
        $this->expectException(ValidationException::class);

        $request = [
            'licence_key' => '',
        ];

        app(CustomerPortalCall::class)->execute($request);
    }
}
