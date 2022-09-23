<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckJournalAccess
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
        $requestedJournalId = $request->route()->parameter('journal');

        $exists = DB::table('journals')->where([
            'vault_id' => $requestedVaultId,
            'id' => $requestedJournalId,
        ])->exists();

        abort_if(! $exists, 401);

        return $next($request);
    }
}
