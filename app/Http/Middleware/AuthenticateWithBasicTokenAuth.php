<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;

class AuthenticateWithBasicTokenAuth
{
    /**
     * The guard factory instance.
     *
     * @var AuthManager
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
     * @param  string|null  $guard
     * @param  string|null  $field
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null, $field = null)
    {
        // Try Bearer authentication, with token in 'password' basic
        if (! $request->bearerToken()) {
            $password = $request->getPassword();
            $request->headers->set('Authorization', 'Bearer '.$password);
        }

        $user = $this->auth->guard('api')->user($request);

        if ($user && (! $request->getUser() || $request->getUser() === $user->email)) {
            $this->auth->setUser($user);

            return $next($request);
        }

        // Basic authentication
        return $this->auth->onceBasic() ?: $next($request);
    }
}
