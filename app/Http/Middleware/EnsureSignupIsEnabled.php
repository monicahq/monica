<?php

namespace App\Http\Middleware;

use App\Helpers\SignupHelper;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSignupIsEnabled
{
    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct(
        private Application $app,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! $this->app[SignupHelper::class]->isEnabled(), 403, trans('Registration is currently disabled'));

        return $next($request);
    }
}
