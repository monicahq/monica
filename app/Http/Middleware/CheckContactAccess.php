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
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedVaultId = $request->route()->parameter('vault');
        $requestedContactId = $request->route()->parameter('contact');

        $exists = DB::table('contacts')->where([
            'vault_id' => $requestedVaultId,
            'id' => $requestedContactId,
        ])->exists();

        abort_if(! $exists, 404);

        return $next($request);
    }
}
