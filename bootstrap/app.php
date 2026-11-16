<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureFlagIsOn;
use App\Http\Middleware\EnsureKeyCanAdminister;
use App\Http\Middleware\EnsureMagicLinkIsEnabled;
use App\Http\Middleware\EnsureRegistrationIsEnabled;
use App\Http\Middleware\EnsureSsoProviderIsAvailable;
use App\Http\Middleware\EnsureSupportIsEnabled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdministrator;
use App\Http\Middleware\InsertServerGenerationTime;
use App\Http\Middleware\MarkPageSearchable;
use App\Http\Middleware\MergeQueryRequestBody;
use App\Http\Middleware\SetApiLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Monicahq\Cloudflare\Http\Middleware\TrustProxies as CloudflareTrustProxies;
use Sentry\Laravel\Integration as SentryIntegration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(
            at: fn (): array => config('app.trusted_hosts') === []
                ? []
                : [...config('app.trusted_hosts'), '^(.+\.)?'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST)).'$'],
            subdomains: false,
        );
        $middleware->throttleApi();
        $middleware->preventRequestForgery(except: [
            'stripe/*',
            'telegram/webhook',
        ]);
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->replace(TrustProxies::class, CloudflareTrustProxies::class);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'administration' => EnsureKeyCanAdminister::class,
            'administrator' => EnsureUserIsAdministrator::class,
            'flag' => EnsureFlagIsOn::class,
            'magic.link' => EnsureMagicLinkIsEnabled::class,
            'portal.timing' => InsertServerGenerationTime::class,
            'query.body' => MergeQueryRequestBody::class,
            'registration' => EnsureRegistrationIsEnabled::class,
            'searchable' => MarkPageSearchable::class,
            'sso' => EnsureSsoProviderIsAvailable::class,
            'support' => EnsureSupportIsEnabled::class,
        ]);
        $middleware->web(remove: [
            SubstituteBindings::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            SubstituteBindings::class,
            AuthenticateSession::class,
        ]);

        $middleware->api(remove: [
            SubstituteBindings::class,
        ]);
        $middleware->api(append: [
            SetApiLocale::class,
            SubstituteBindings::class,
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('auth.login.new'));
        $middleware->redirectUsersTo(fn (): string => route('home.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The passkey ceremony endpoints are named one by one rather than matched by prefix, because
        // removing a passkey sits under the same prefix and is an ordinary form submission.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('mcp')
                || $request->is('passkeys/*')
                || $request->is('user/passkeys')
                || $request->is('user/passkeys/options'),
        );
        SentryIntegration::handles($exceptions);
    })->create();
