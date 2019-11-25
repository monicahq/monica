<?php

namespace App\Http\Middleware;

use App\Models\User\User;
use Illuminate\Support\Facades\App;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        if (App::environment() == 'documentation') {
            $user = factory(User::class)->create();
            $user->account->populateDefaultFields();
            $user->acceptPolicy();
            $user->wasRecentlyCreated = false;

            $this->auth->guard(null)->setUser($user);
            $this->auth->shouldUse(null);

            return;
        }

        return parent::authenticate($request, $guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
