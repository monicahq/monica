<?php

namespace Tests\Traits;

use App\Models\User\User;

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
            $user = factory(User::class)->create();
            $user->account->populateDefaultFields($user->account);
            $user->acceptPolicy();
        }

        $this->be($user);

        return $user;
    }
}
