<?php

namespace Tests\Unit\Services\Account\Subscription;

use Exception;
use Tests\TestCase;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Subscription\ActivateLicenceKey;

class ActivateLicenceKeyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_activates_a_licence_key()
    {
        config(['monica.licence_key_encryption_key' => '123']);
        Http::fake();

        $key = 'W3siZnJlcXVlbmN5IjoibW9udGhseSIsInB1cmNoYXNlcl9lbWFpbCI6ImFkbWluQGFkbWluLmNvbSIsIm5leHRfY2hlY2tfYXQiOiIyMDIyLTA0LTAzVDAwOjAwOjAwLjAwMDAwMFoifV0=';
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => $key,
        ];

        app(ActivateLicenceKey::class)->execute($request);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'licence_key' => 'W3siZnJlcXVlbmN5IjoibW9udGhseSIsInB1cmNoYXNlcl9lbWFpbCI6ImFkbWluQGFkbWluLmNvbSIsIm5leHRfY2hlY2tfYXQiOiIyMDIyLTA0LTAzVDAwOjAwOjAwLjAwMDAwMFoifV0=',
            'valid_until_at' => '2022-04-03 00:00:00',
            'purchaser_email' => 'admin@admin.com',
            'frequency' => 'monthly',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(ActivateLicenceKey::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_licence_key_does_not_exist()
    {
        $this->expectException(Exception::class);

        Http::fake(function ($request) {
            return Http::response('', 404);
        });

        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => '',
        ];

        app(ActivateLicenceKey::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_licence_key_is_not_valid_anymore()
    {
        $this->expectException(Exception::class);

        Http::fake(function ($request) {
            return Http::response('', 900);
        });

        $key = 'W3siZnJlcXVlbmN5IjoibW9udGhseSIsInB1cmNoYXNlcl9lbWFpbCI6ImFkbWluQGFkbWluLmNvbSIsIm5leHRfY2hlY2tfYXQiOiIyMDIyLTA0LTAzVDAwOjAwOjAwLjAwMDAwMFoifV0=';
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'licence_key' => $key,
        ];

        app(ActivateLicenceKey::class)->execute($request);
    }
}
