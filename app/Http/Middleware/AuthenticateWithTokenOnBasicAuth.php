<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;

/**
 * Authenticate user with Basic Authentication, with two methods:
 * - Basic auth: login + password
 * - Bearer on basic: login + api token.
 */
class AuthenticateWithTokenOnBasicAuth
{
    /**
     * The guard factory instance.
     *
     * @var AuthManager
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  AuthManager  $auth
     * @return void
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate($request);

        return $next($request);
    }

    /**
     * Handle authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    private function authenticate($request)
    {
        if ($this->auth->guard()->check()) {
            return;
        }

        // Try Bearer authentication, with token in 'password' field on basic auth
        if (! $request->bearerToken()) {
            $password = $request->getPassword();
            $request->headers->set('Authorization', 'Bearer '.$password);
        }

        $user = null;
        $guard = $this->auth->guard('api');
        if (method_exists($guard, 'setRequest')) {
            $user = $guard->setRequest($request)->user();
        }

        if ($user && (! $request->getUser() || $request->getUser() === $user->email)) {
            $this->auth->guard()->setUser($user);
        } else {
            // Basic authentication
            $this->auth->guard()->onceBasic();
        }
    }
}
