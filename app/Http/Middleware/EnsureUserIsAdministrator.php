<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep everybody who does not run the instance out of the admin space.
 *
 * The refusal is a 404 and never a 403, and the group this guards carries no
 * `auth` middleware. Both are deliberate. A 403 says there is something here,
 * and a redirect to the sign in screen says the same thing to somebody who is
 * not signed in. A regular user guessing at an address has to be told exactly
 * what they would be told about an address that does not exist.
 *
 * Being active is checked here rather than left to the `active` middleware,
 * because this group does not run it, and because a capacity granted to
 * somebody who was then deactivated must not be a way back in.
 */
class EnsureUserIsAdministrator
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdministrator()) {
            abort(404);
        }

        return $next($request);
    }
}
