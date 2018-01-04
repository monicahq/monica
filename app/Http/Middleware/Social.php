<?php

namespace App\Http\Middleware;

use Closure;

class Social
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
        if(!in_array(strtolower($request->service), array_keys(config('social.services'))))
        {
            return redirect(route('login'));
        }

        if(!config('social.services.' . $request->service . '.allow_signup'))
        {
            return redirect(route('login'));
        }

        return $next($request);
    }
}
