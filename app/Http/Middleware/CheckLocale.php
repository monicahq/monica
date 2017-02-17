<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class CheckLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            \App::setLocale(Auth::user()->locale);
        }

        return $next($request);
    }
}
