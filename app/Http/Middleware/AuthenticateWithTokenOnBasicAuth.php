<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Authenticate user with Basic Authentication, with Sanctum token on password field.
 *
 * Examples:
 *   curl -u "email@example.com:$TOKEN" -X PROPFIND https://localhost/dav/
 */
class AuthenticateWithTokenOnBasicAuth
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
        if (! $this->basicAuth($request)) {
            $this->failedBasicResponse();
        }

        return $next($request);
    }

    /**
     * Try Bearer authentication, with token in 'password' field on basic auth.
     */
    private function basicAuth(Request $request): bool
    {
        if (($user = $this->sanctumUser($request)) !== null) {
            $this->auth->guard()->setUser($user);

            return true;
        }

        return false;
    }

    /**
     * Authenticate user.
     */
    private function sanctumUser(Request $request): ?User
    {
        /** @var ?User */
        $user = $this->sanctum()->setRequest($request)->user();

        // if there is no bearer token PHP_AUTH_USER header must match user email
        if ($user !== null
            && $user->currentAccessToken() !== null
            && $request->bearerToken() !== null
            && $request->getUser() !== $user->email) {
            return null;
        }

        return $user;
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

    /**
     * Get the response for basic authentication.
     *
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function failedBasicResponse(): void
    {
        throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
    }
}
