<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Laravel\Sanctum\Sanctum;

class EnsureDavRequestsAreStateful
{
    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct(
        private Application $app,
        private Auth $auth
    ) {}

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return (new Pipeline($this->app))->send($request)->through($this->auth->guard()->check()
            ? $this->authenticated()
            : $this->authenticate()
        )
            ->then(fn (Request $request) => $next($request));
    }

    private function authenticated(): array
    {
        return $this->app->environment('local') ? [
            SanctumSetUser::class,
        ] : [];
    }

    private function authenticate(): array
    {
        return [
            function (Request $request, Closure $next) {
                Sanctum::getAccessTokenFromRequestUsing(fn (Request $request): ?string => $request->bearerToken() ?? $request->getPassword()
                );

                return $next($request);
            },
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            AuthenticateWithTokenOnBasicAuth::class,
        ];
    }
}
