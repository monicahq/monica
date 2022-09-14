<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckVaultPermissionAtLeastManager
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

        $exists = DB::table('user_vault')->where([
            ['vault_id', '=', $requestedVaultId],
            ['user_id', '=', Auth::id()],
            ['permission', '<=', 100],
        ])->exists();

        abort_if(! $exists, 401);

        return $next($request);
    }
}
