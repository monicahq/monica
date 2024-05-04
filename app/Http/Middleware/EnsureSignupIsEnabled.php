<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\SignupHelper;
use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSignupIsEnabled
{
    public function __construct(
        protected SignupHelper $signupHelper,
        protected Translator $translator
    )
    { }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->signupHelper->isEnabled()) {
            abort(Response::HTTP_FORBIDDEN, $this->translator->get('auth.signup_disabled'));
        }

        return $next($request);
    }
}
