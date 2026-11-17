<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\ChangelogAvailability;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChangelogIsEnabled
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! ChangelogAvailability::isEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}
