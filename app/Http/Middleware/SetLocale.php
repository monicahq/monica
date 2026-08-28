<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Put the interface in the right language. A choice made in this session wins,
 * because it is the most recent thing the visitor asked for, then the language
 * on their account, then the fallback of the application.
 *
 * An API request carries a token rather than a session, so there is nothing to
 * ask on that surface and the first of the three is simply skipped. Without the
 * check, reading from a session that was never started throws.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferred($request);

        if (array_key_exists($locale, config('monica.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function preferred(Request $request): string
    {
        $session = $request->hasSession() ? $request->session()->get('locale') : null;

        if ($session !== null) {
            return $session;
        }

        $user = $request->user();

        if ($user === null) {
            return config('app.locale');
        }

        return $user->locale ?? config('app.locale');
    }
}
