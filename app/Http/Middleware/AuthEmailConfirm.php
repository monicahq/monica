<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthEmailConfirm
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
        if (Auth::check() && ! auth()->user()->confirmed && config('monica.signup_double_optin')) {
            // Logout the user
            Auth::guard()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')
                ->with('confirmation-danger', trans('confirmation::confirmation.again'));
        }

        return $next($request);
    }
}
