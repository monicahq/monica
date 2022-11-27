<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Api\Controllers;

use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;

class UserControllerTest extends ApiTestCase
{
    /** @test */
    public function it_gets_the_current_user(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $user = $this->createUser();
        Sanctum::actingAs($user, ['read']);

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
        $user = $this->createUser();
        Sanctum::actingAs($user, ['read']);

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
}
