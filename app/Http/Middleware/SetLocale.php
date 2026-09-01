<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\LocaleResolution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Put a screen in the right language.
 *
 * Runs after the session has started, because the first thing it reads is the
 * session, and before route bindings are resolved, so a record is looked up with
 * the language already settled. Registered on the whole web group rather than
 * route by route, so no screen can be forgotten.
 *
 * Nothing is written down here. A language somebody actually picked is recorded
 * by the screen they picked it on; a language merely worked out from their
 * browser is worked out again next time, which is what keeps this product from
 * leaving anything on the device of somebody who never asked for it.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(LocaleResolution::forWeb($request));

        return $next($request);
    }
}
