<?php

namespace Tests\Traits;

use App\Models\User\User;
use Laravel\Passport\Passport;

trait ApiSignIn
{
    /**
     * Create a user and sign in as that user.
     *
     * @param null $user
     * @return mixed
     */
    public function signIn()
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user);

        return $user;
    }
}
