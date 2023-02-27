<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckSliceOfLifeAccess
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedJournalId = $request->route()->parameter('journal');
        $requestedSliceOfLifeId = $request->route()->parameter('slice');

        $exists = DB::table('slices_of_life')->where([
            'journal_id' => $requestedJournalId,
            'id' => $requestedSliceOfLifeId,
        ])->exists();

        abort_if(! $exists, 404);

        return $next($request);
    }
}
