<?php

use App\Http\Middleware\EnsureSignupIsEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use LaravelWebauthn\Http\Middleware\WebauthnMiddleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
        // Without this, requests behind a reverse proxy (Docker/Apache/nginx) are seen
        // as plain HTTP, so Monica generates http:// redirect and asset URLs even when
        // served over HTTPS, breaking the post-login redirect (see #7696).
        $middleware->trustProxies(at: match (true) {
            is_null(env('APP_TRUSTED_PROXIES')) => [],
            env('APP_TRUSTED_PROXIES') === '*' => '*',
            default => array_map('trim', explode(',', (string) env('APP_TRUSTED_PROXIES'))),
        });
        $middleware->validateCsrfTokens(except: [
            '/dav',
            '/dav/*',
            '/telegram/webhook/*',
        ]);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'webauthn' => WebauthnMiddleware::class,
            'monica.signup_is_enabled' => EnsureSignupIsEnabled::class,
        ]);
        $middleware->web(remove: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $middleware->web(append: [
            \CodeZero\Localizer\Middleware\SetLocale::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })
    ->create();
