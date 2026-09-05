<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdministrator;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'administrator' => EnsureUserIsAdministrator::class,
        ]);
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->web(remove: [
            SubstituteBindings::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            SubstituteBindings::class,
        ]);

        // The api group has no session and no cookies, so it reads the language
        // somewhere else and in another order, which is a second middleware
        // rather than a condition inside the first.
        $middleware->api(prepend: [
            SetApiLocale::class,
        ]);

        // The framework looks for routes literally named `login` and `home`.
        // Ours are named after the domain they belong to, so it has to be told
        // where to send somebody who is signed in when they should not be, and
        // somebody who is not when they should be.
        $middleware->redirectGuestsTo(fn (): string => route('auth.login.new'));
        $middleware->redirectUsersTo(fn (): string => route('home.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errors are rendered as JSON only where something other than a browser
        // is asking. The passkey routes are the second such place: the whole
        // exchange is fetch calls from one Alpine component, so a refusal has to
        // come back as a body it can read and show, not as a redirect carrying
        // the message in a session it never looks at.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is(config('webauthn.prefix', 'webauthn').'/*'),
        );
    })->create();
