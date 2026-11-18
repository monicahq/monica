<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBlackBookdModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->account->blackbookd_enabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'The BlackBookd private module is disabled for this account.',
                ], 403);
            }

            abort(403, 'The BlackBookd private module is disabled for this account.');
        }

        return $next($request);
    }
}