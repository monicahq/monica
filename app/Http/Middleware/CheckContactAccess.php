<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckContactAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedVaultId = $request->route()->parameter('vault');
        $requestedContactId = $request->route()->parameter('contact');

        $exists = DB::table('contacts')->where('vault_id', $requestedVaultId)
            ->where('id', $requestedContactId)
            ->count() > 0;

        if ($exists) {
            return $next($request);
        } else {
            abort(401);
        }
    }
}
