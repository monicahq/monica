<?php

namespace Tests\Traits;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait ApiSignIn
{
    /**
     * Create a user and sign in as that user.
     *
     * @param  null  $user
     * @return mixed
     */
    public function signIn()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
