<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PreviewListEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('monica.registration_enabled') && PreviewListEntry::findByAdmissionToken((string) $request->input('admission')) === null) {
            abort(404);
        }

        return $next($request);
    }
}
