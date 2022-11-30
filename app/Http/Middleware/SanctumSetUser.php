<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;

class SanctumSetUser
{
    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(
        private Auth $auth
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->sanctum()->setUser($request->user()->withAccessToken(new TransientToken));

        return $next($request);
    }

    /**
     * Get sanctum guard.
     *
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function sanctum(): RequestGuard
    {
        /** @var \Illuminate\Auth\RequestGuard */
        $guard = $this->auth->guard('sanctum');

        return $guard;
    }
}
