<?php

namespace App\Http\Middleware;

use Auth;
use Carbon\Carbon;
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
            Carbon::setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
