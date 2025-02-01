<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\LoginRateLimiter;
use LaravelWebauthn\Facades\Webauthn;

class RedirectIfTwoFactorAuthenticatable
{
    /**
     * Create a new action instance.
     */
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter
    ) {}

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->validateCredentials($request);

        if ((optional($user)->two_factor_secret && ! is_null(optional($user)->two_factor_confirmed_at))
            || Webauthn::enabled($user)) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }

    /**
     * Attempt to validate the incoming credentials.
     */
    protected function validateCredentials(Request $request): ?User
    {
        return tap(User::where('email', $request->email)->first(), function ($user) use ($request) {
            if (! $user || ! $this->guard->getProvider()->validateCredentials($user, ['password' => $request->password])) {
                $this->fireFailedEvent($request, $user);

                $this->throwFailedAuthenticationException($request);
            }
        });
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request): void
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     */
    protected function fireFailedEvent(Request $request, ?User $user = null): void
    {
        event(new Failed(config('fortify.guard'), $user, [
            'email' => $request->email,
            'password' => $request->password,
        ]));
    }

    /**
     * Get the two factor authentication enabled response.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function twoFactorChallengeResponse(Request $request, ?User $user)
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);

        return $request->wantsJson()
                    ? response()->json(['two_factor' => true])
                    : redirect()->route('two-factor.login');
    }
}
