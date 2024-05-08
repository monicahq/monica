<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\SignupHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSignupIsEnabled
{
    public function __construct(
        protected SignupHelper $signupHelper,
    )
    { }

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(!$this->signupHelper->isEnabled(), 403, trans('Registration is currently disabled'));

        return $next($request);
    }
}
