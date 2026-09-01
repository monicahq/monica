<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\LocaleResolution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Put an API response in the right language.
 *
 * A separate middleware from the one the screens use, because the two read the
 * same places in a different order: a caller naming a language on the request
 * has asked for it, and outranks the preference stored against whoever the token
 * belongs to, where a browser announcing one has only ever been configured and
 * does not.
 *
 * Sets the language and records nothing, so answering a call leaves no session
 * and no cookie behind. The api group carries neither the middleware that saves
 * a session nor the one that attaches a queued cookie, so writing would be
 * useless rather than harmful, but a promise the product makes should not rest
 * on the contents of a framework array it does not own.
 */
class SetApiLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(LocaleResolution::forApi($request));

        return $next($request);
    }
}
