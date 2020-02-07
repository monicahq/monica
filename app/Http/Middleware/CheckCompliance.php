<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\ComplianceHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckCompliance
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
        if ($request->isMethod('post')) {
            return $next($request);
        }

        if (Route::currentRouteName() == 'compliance') {
            return $next($request);
        }

        if (Auth::check()) {
            if (! ComplianceHelper::isCompliantWithCurrentTerm(auth()->user())) {
                return redirect()->route('compliance');
            }
        }

        return $next($request);
    }
}
