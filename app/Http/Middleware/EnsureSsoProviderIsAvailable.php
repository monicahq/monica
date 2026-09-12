<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SsoProviderEnum;
use App\Helpers\SsoProviderAvailability;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoProviderIsAvailable
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $named = $request->route()?->parameter('provider');

        if ($named === null) {
            if (! SsoProviderAvailability::anyOnThisInstance()) {
                abort(404);
            }

            return $next($request);
        }

        $provider = SsoProviderEnum::tryFrom((string) $named);

        if ($provider === null || ! SsoProviderAvailability::isAvailable($provider)) {
            abort(404);
        }

        return $next($request);
    }
}
