<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Put the interface in the right language. A choice made in this session wins,
 * because it is the most recent thing the visitor asked for, then the language
 * on their account, then the one their company runs in, then the fallback of
 * the application.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->preferred($request);

        if (array_key_exists($locale, config('officelife.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function preferred(Request $request): string
    {
        $session = $request->session()->get('locale');

        if ($session !== null) {
            return $session;
        }

        $user = $request->user();

        if ($user === null) {
            return config('app.locale');
        }

        return $user->locale ?? $user->company->locale ?? config('app.locale');
    }
}
