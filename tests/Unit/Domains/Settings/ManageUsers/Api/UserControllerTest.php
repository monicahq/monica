<?php

namespace Tests\Unit\Domains\Settings\ManageUsers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_current_user(): void
    {
        $user = $this->createUser();

        Sanctum::actingAs($user, ['read']);

        $response = $this->get('/api/user');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }
}
