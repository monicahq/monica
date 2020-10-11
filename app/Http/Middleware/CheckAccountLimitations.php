<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\AccountHelper;
use Illuminate\Support\Facades\Auth;

class CheckAccountLimitations
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
        if (Auth::check() && AccountHelper::hasLimitations(auth()->user()->account)) {
            abort(402);
        }

        return $next($request);
    }
}
