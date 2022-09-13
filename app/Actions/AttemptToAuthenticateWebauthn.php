<?php

namespace App\Actions;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Facades\Webauthn as WebauthnFacade;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Services\Webauthn;

class AttemptToAuthenticateWebauthn
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected StatefulGuard $guard;

    /**
     * The login rate limiter instance.
     *
     * @var \LaravelWebauthn\Services\LoginRateLimiter
     */
    protected LoginRateLimiter $limiter;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @param  \LaravelWebauthn\Services\LoginRateLimiter  $limiter
     * @return void
     */
    public function __construct(StatefulGuard $guard, LoginRateLimiter $limiter)
    {
        $this->guard = $guard;
        $this->limiter = $limiter;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        if ($this->attemptValidateAssertion($request)
            || $this->attemptLogin($this->filterCredentials($request), $request->boolean('remember'))) {
            return $next($request);
        }

        $this->throwFailedAuthenticationException($request);

        return null;
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  array  $challenge
     * @param  bool  $remember
     * @return bool
     */
    protected function attemptLogin(array $challenge, bool $remember = false): bool
    {
        return $this->guard->attempt($challenge, $remember);
    }

    /**
     * Attempt to validate assertion for authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptValidateAssertion(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        $result = WebauthnFacade::validateAssertion($user, $this->filterCredentials($request));

        if (! $result) {
            $this->fireFailedEvent($request, $user);

            $this->throwFailedAuthenticationException($request);

            return false; // @codeCoverageIgnore
        }

        return true;
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.login_failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return void
     */
    protected function fireFailedEvent(Request $request, ?Authenticatable $user = null)
    {
        event(new Failed(config('webauthn.guard'), $user, [
            Webauthn::username() => $user !== null
                ? $user->{Webauthn::username()}
                : $request->{Webauthn::username()},
        ]));
    }

    /**
     * Get array of webauthn credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function filterCredentials(Request $request): array
    {
        return $request->only(['id', 'rawId', 'response', 'type']);
    }
}
