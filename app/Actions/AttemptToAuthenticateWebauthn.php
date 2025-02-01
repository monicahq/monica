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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter
    ) {}

    /**
     * Handle the incoming request.
     *
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
     */
    protected function attemptLogin(array $challenge, bool $remember = false): bool
    {
        return $this->guard->attempt($challenge, $remember);
    }

    /**
     * Attempt to validate assertion for authenticated user.
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
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans_ignore('webauthn::errors.login_failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
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
     */
    protected function filterCredentials(Request $request): array
    {
        return $request->only(['id', 'rawId', 'response', 'type']);
    }
}
