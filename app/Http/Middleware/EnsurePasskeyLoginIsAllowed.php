<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\RefusePasskeyLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The stage the passkey sign-in pipeline runs before it looks at the signature.
 *
 * It resolves the credential to its owner through the provider, then hands that
 * candidate to RefusePasskeyLogin, which decides. Running before
 * AttemptToAuthenticate matters: a deactivated user must be refused without the
 * assertion ever being verified, and without anything being written down about
 * the attempt beyond the refusal.
 *
 * This lives in Middleware rather than Listeners because that is what it is. It
 * takes a request and a closure, and the pipeline calls it exactly as the
 * framework calls middleware. It is registered through
 * `Webauthn::authenticateThrough()` rather than on a route.
 */
class EnsurePasskeyLoginIsAllowed
{
    public function handle(Request $request, Closure $next): mixed
    {
        $candidate = Auth::guard((string) config('webauthn.guard', 'web'))
            ->getProvider()
            ->retrieveByCredentials($request->only(['id', 'rawId', 'type', 'response']));

        new RefusePasskeyLogin(candidate: $candidate)->execute();

        return $next($request);
    }
}
