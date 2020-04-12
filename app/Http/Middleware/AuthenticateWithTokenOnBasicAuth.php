<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

/**
 * Authenticate user with Basic Authentication, with Passport token on password field.
 *
 * Examples:
 *   curl -u "email@example.com:$TOKEN" -X PROPFIND https://localhost/dav/
 *   curl -u ":$TOKEN" -X PROPFIND https://localhost/dav/
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

        $user = $this->tryBearer($request);

        if ($user && (! $request->getUser() || $request->getUser() === $user->email)) {
            $this->auth->guard()->setUser($user);
        }
    }

    /**
     * Try Bearer authentication, with token in 'password' field on basic auth
     *
     * @param  \Illuminate\Http\Request  $request
     */
    private function tryBearer(Request  $request)
    {
        // Try Bearer authentication, with token in 'password' field on basic auth
        if (! $request->bearerToken()) {
            $password = $request->getPassword();
            $request->headers->set('Authorization', 'Bearer '.$password);
        }

        $headerUser = $request->getUser();
        $user = null;
        try {
            $request->headers->set('PHP_AUTH_USER', '');

            $guard = $this->auth->guard('api');

            if (method_exists($guard, 'setRequest')) {
                $user = $guard->setRequest($request)->user();
            }
        } finally {
            $request->headers->set('PHP_AUTH_USER', $headerUser);
        }

        return $user;
    }
}
