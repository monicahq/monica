<?php

namespace App\Http\Middleware;

use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Support\Facades\App;

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
        $locale = $request->query('lang');

        if (empty($locale)) {
            $locale = LocaleHelper::getLocale();
        }

        App::setLocale($locale);

        return $next($request);
    }
}
