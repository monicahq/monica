<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckGroupAccess
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
        $requestedGroupId = $request->route()->parameter('group');

        $exists = DB::table('groups')->where([
            'vault_id' => $requestedVaultId,
            'id' => $requestedGroupId,
        ])->exists();

        abort_if(! $exists, 404);

        return $next($request);
    }
}
