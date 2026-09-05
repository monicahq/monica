<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\LocaleResolution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(LocaleResolution::forApi($request));

        return $next($request);
    }
}
