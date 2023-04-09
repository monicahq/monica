<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Api\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\ApiTestCase;

class UserControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_current_user(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['read']);

        $response = $this->get('/api/user');

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'created_at' => '2018-01-01T00:00:00Z',
                'updated_at' => '2018-01-01T00:00:00Z',
                'links' => [
                    'self' => env('APP_URL')."/api/users/{$user->id}",
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_a_list_of_users(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['read']);

        $response = $this->get('/api/users');

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                0 => [
                    'id' => $user->id,
                    'name' => $user->first_name.' '.$user->last_name,
                    'email' => $user->email,
                    'created_at' => '2018-01-01T00:00:00Z',
                    'updated_at' => '2018-01-01T00:00:00Z',
                    'links' => [
                        'self' => env('APP_URL')."/api/users/{$user->id}",
                    ],
                ],
            ],
            'links' => $this->links('/api/users'),
            'meta' => $this->meta('/api/users'),
        ]);
    }

    /** @test */
    public function it_gets_a_user_details(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser(['read']);

        $response = $this->get('/api/users/'.$user->id);

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->first_name.' '.$user->last_name,
                'email' => $user->email,
                'created_at' => '2018-01-01T00:00:00Z',
                'updated_at' => '2018-01-01T00:00:00Z',
                'links' => [
                    'self' => env('APP_URL')."/api/users/{$user->id}",
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_an_exception_getting_unexisting_user(): void
    {
        $this->createUser(['read']);

        $uuid = (string) Str::orderedUuid();

        $response = $this->get("/api/users/$uuid");

        $response->assertResourceNotFound();
    }
}
