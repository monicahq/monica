<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turn a deactivation into the end of a session.
 *
 * Signing in already refuses somebody who is not active, but a user who is
 * deactivated while signed in elsewhere would otherwise carry on until their
 * session expired. This is checked on the request rather than by deleting rows
 * from the session table, because that table only exists under the database
 * driver, and this has to hold under every one of them.
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login.new');
    }
}
