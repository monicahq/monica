<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use Jenssegers\Date\Date;

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
            $locale = Auth::user()->locale;
        } else {
            $locale = app('language.detector')->detect() ?: config('app.locale');
        }

        \App::setLocale($locale);
        Carbon::setLocale($locale);
        Date::setLocale($locale);

        return $next($request);
    }
}
