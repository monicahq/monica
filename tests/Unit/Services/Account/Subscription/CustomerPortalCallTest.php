<?php

namespace Tests\Unit\Services\Account\Subscription;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NoCustomerPortalSetException;
use App\Exceptions\CustomerPortalWrongCredentials;
use App\Exceptions\NoCustomerPortalSecretsException;
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
            'https://fake.test/api/validate' => Http::response(['data' => 'ok'], 200),
        ]);

        $request = [
            'licence_key' => 'key',
        ];

        $response = app(CustomerPortalCall::class)->execute($request);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals(['data' => 'ok'], $response['data']);

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
    public function it_fails_if_the_licence_key_is_empty()
    {
        $this->expectException(ValidationException::class);

        $request = [
            'licence_key' => '',
        ];

        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_customer_portal_is_not_set()
    {
        config(['monica.customer_portal_url' => '']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => '1']);

        $this->expectException(NoCustomerPortalSetException::class);

        $request = [
            'licence_key' => 'test',
        ];

        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_client_id_is_not_set()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => null]);
        config(['monica.customer_portal_client_secret' => '1']);

        $this->expectException(NoCustomerPortalSecretsException::class);

        $request = [
            'licence_key' => 'test',
        ];

        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_client_secret_is_not_set()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => null]);

        $this->expectException(NoCustomerPortalSecretsException::class);

        $request = [
            'licence_key' => 'test',
        ];

        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_stores_access_token_into_cache()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => '1']);

        Cache::flush();

        $this->assertFalse(Cache::has('customer_portal.access_token'));

        Http::fake([
            'https://fake.test/oauth/token' => Http::response(['access_token' => '123']),
            'https://fake.test/api/validate' => Http::response([], 200),
        ]);

        $request = [
            'licence_key' => 'key',
        ];

        app(CustomerPortalCall::class)->execute($request);

        $this->assertTrue(Cache::has('customer_portal.access_token'));
    }

    /** @test */
    public function it_throw_an_exception_if_no_access_token()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => '1']);

        Cache::flush();

        $this->assertFalse(Cache::has('customer_portal.access_token'));

        Http::fake([
            'https://fake.test/oauth/token' => Http::response([]),
            'https://fake.test/api/validate' => Http::response([], 200),
        ]);

        $request = [
            'licence_key' => 'key',
        ];

        $this->expectException(CustomerPortalWrongCredentials::class);
        app(CustomerPortalCall::class)->execute($request);
    }

    /** @test */
    public function it_throw_an_exception_if_oauth_token_send_bad_status()
    {
        config(['monica.customer_portal_url' => 'https://fake.test']);
        config(['monica.customer_portal_client_id' => '1']);
        config(['monica.customer_portal_client_secret' => '1']);

        Cache::flush();

        $this->assertFalse(Cache::has('customer_portal.access_token'));

        Http::fake([
            'https://fake.test/oauth/token' => Http::response([], 500),
        ]);

        $request = [
            'licence_key' => 'key',
        ];

        $this->expectException(CustomerPortalWrongCredentials::class);
        app(CustomerPortalCall::class)->execute($request);
    }
}
