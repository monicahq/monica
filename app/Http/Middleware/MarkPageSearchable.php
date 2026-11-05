<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response;

class MarkPageSearchable
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('monica.search_engine_indexing')) {
            Head::searchableByRobots();
            $request->attributes->set('searchable', true);
        }

        return $next($request);
    }
}
