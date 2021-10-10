<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
     *
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

        if (! $this->basicAuth($request)) {
            $this->failedBasicResponse();
        }
    }

    /**
     * Try Bearer authentication, with token in 'password' field on basic auth.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    private function basicAuth(Request $request)
    {
        if (! $this->assertToken($request)) {
            return false;
        }

        $user = $this->authUser($request);

        // match User header if present
        if ($user && (! $request->getUser() || $request->getUser() === $user->email)) {
            $this->auth->guard()->setUser($user);

            return true;
        }

        return false;
    }

    /**
     * Authenticate user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return User|null
     */
    private function authUser(Request $request): ?User
    {
        $headerUser = $request->getUser();
        $user = null;
        try {
            // Remove User from header request as Laravel auth will not authenticate using Bearer token
            $request->headers->set('PHP_AUTH_USER', '');

            /** @var \Illuminate\Auth\RequestGuard */
            $guard = $this->auth->guard('api');

            /** @var ?User */
            $user = $guard->setRequest($request)
                ->user();
        } finally {
            $request->headers->set('PHP_AUTH_USER', $headerUser);
        }

        return $user;
    }

    /**
     * Assert Bearer token is present.
     * If not using 'password' field on basic auth as Bearer token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function assertToken(Request $request): bool
    {
        if (! $request->bearerToken()) {
            $password = $request->getPassword();
            $request->headers->set('Authorization', 'Bearer '.$password);
        }

        return true;
    }

    /**
     * Get the response for basic authentication.
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function failedBasicResponse()
    {
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }
}
