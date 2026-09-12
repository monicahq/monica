<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Override;

/**
 * Ask for a credential before a change to the passkeys, unless the account holds no password.
 *
 * An account that signs in with a passkey alone has nothing else to confirm with, and the passkey the
 * gate would ask for may be on the telephone that was just lost. Adding a new one is the way back
 * from that, and the sign up flow lands on it before any password exists, so the gate stands aside
 * while there is no password to give.
 */
class EnsurePasswordIsConfirmedWhenThereIsOne extends RequirePassword
{
    /**
     * @param  Request  $request
     * @param  string|null  $redirectToRoute
     * @param  string|int|null  $passwordTimeoutSeconds
     * @return mixed
     */
    #[Override]
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        if ($request->user()?->password === null) {
            return $next($request);
        }

        return parent::handle(
            $request,
            $next,
            $redirectToRoute ?? 'auth.confirmPassword.new',
            $passwordTimeoutSeconds,
        );
    }
}
