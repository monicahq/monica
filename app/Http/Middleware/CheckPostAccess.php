<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckPostAccess
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedJournalId = $request->route()->parameter('journal');
        $requestedPostId = $request->route()->parameter('post');

        $exists = DB::table('posts')->where([
            'journal_id' => $requestedJournalId,
            'id' => $requestedPostId,
        ])->exists();

        abort_if(! $exists, 404);

        return $next($request);
    }
}
