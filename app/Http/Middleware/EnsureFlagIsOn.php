<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\FlagState;
use Closure;
use Illuminate\Http\Request;
use Laravel\Nightwatch\Facades\Nightwatch;
use Symfony\Component\HttpFoundation\Response;

class EnsureFlagIsOn
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next, string $flag): Response
    {
        Nightwatch::sample(config('nightwatch.sampling.features'));

        if (! FlagState::isOn($flag, $request->user()?->account)) {
            abort(404);
        }

        return $next($request);
    }
}
