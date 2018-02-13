<?php

namespace Tests\Traits;

trait SignIn
{
    /**
     * Create a user and sign in as that user. If a user
     * object is passed, then sign in as that user.
     *
     * @param null $user
     * @return mixed
     */
    public function signIn($user = null)
    {
        if (is_null($user)) {
            $user = factory('App\User')->create();
        }

        $this->be($user);

        return $user;
    }
}
