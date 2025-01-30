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
     * @return void
     */
    public function __construct(
        private Auth $auth
    ) {}

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->sanctum()->setUser($request->user()->withAccessToken(new TransientToken)); // @phpstan-ignore-line

        return $next($request);
    }

    /**
     * Get sanctum guard.
     */
    protected function sanctum(): RequestGuard
    {
        /** @var \Illuminate\Auth\RequestGuard */
        $guard = $this->auth->guard('sanctum');

        return $guard;
    }
}
