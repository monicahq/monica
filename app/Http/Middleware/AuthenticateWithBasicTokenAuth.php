<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticateWithBasicTokenAuth
{
    /**
     * The guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
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
