<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\VerifyPasskey;
use App\Enums\SsoProviderEnum;
use App\Helpers\InstanceSettings;
use App\Helpers\LocaleCollation;
use App\Helpers\MaterializedDatabaseEngine;
use App\Http\Responses\PasskeyConfirmationResponse;
use App\Http\Responses\PasskeyDeletedResponse;
use App\Http\Responses\PasskeyLoginResponse;
use App\Models\Account;
use App\Models\Passkey;
use App\Models\User;
use App\ViewModels\Auth\OAuthAuthorizationViewModel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Passkeys\Passkeys;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Jobs\MakeSearchableUniquely;
use Laravel\Scout\Jobs\RemoveFromSearchUniquely;
use Laravel\Scout\Scout;
use Monicahq\Cloudflare\Facades\CloudflareProxies;
use Monicahq\Cloudflare\LaravelCloudflare;
use Override;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    private const int MAX_LOGIN_ATTEMPTS = 5;

    #[Override]
    public function register(): void
    {
        $this->configurePasskeys();

        Passport::$deviceCodeGrantEnabled = false;
    }

    /**
     * Put what an administrator chose in front of what the deployment supplied,
     * before anything else boots and reads a value.
     *
     * The web guard reads how long it remembers a browser from its configuration
     * each time it is built, so every way in, the passkey package's included,
     * gets the instance's duration rather than the framework's 400 days.
     */
    private function applyInstanceSettings(): void
    {
        InstanceSettings::stashDeploymentValues();
        InstanceSettings::apply();

        config()->set('auth.guards.web.remember', (int) config('monica.remember_duration_days') * 24 * 60);
    }

    /**
     * Register the driver of every provider that needs a package of its own.
     * Which packages are there is read from configuration rather than tested
     * here, so a test reaches either state, and a package that is absent is
     * never named.
     */
    private function configureSingleSignOn(): void
    {
        foreach (SsoProviderEnum::cases() as $provider) {
            $driver = $provider->driverClass();

            if ($driver === null || ! in_array($provider->value, config('monica.sso_installed', []), true)) {
                continue;
            }

            Event::listen(function (SocialiteWasCalled $event) use ($provider, $driver): void {
                $event->extendSocialite($provider->value, $driver);
            });
        }
    }

    private function configurePasskeys(): void
    {
        Passkeys::usePasskeyModel(Passkey::class);
        Passkeys::useUserModel(User::class);

        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
        $this->app->singleton(PasskeyConfirmationResponseContract::class, PasskeyConfirmationResponse::class);
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        $this->applyInstanceSettings();
        $this->forceSecureUrls();
        $this->configureTrustedProxies();
        $this->configureRateLimiting();
        $this->configureCollation();
        $this->configureDefaultsProduction();
        $this->configureBilling();
        $this->configureSingleSignOn();
        $this->configurePassport();
        $this->configureSearch();
        $this->configureFlags();
        $this->configureQueues();
        $this->configureAssets();
        $this->refuseDeactivatedUsers();
    }

    /**
     * `high`, `default` and `low` name priorities, not queues: every dispatch and every worker list
     * says them, and the forward turns each into the queue the operator chose, on push and on poll
     * alike. A blank name forwards nothing, so the priority stays on its shipped queue.
     */
    private function configureQueues(): void
    {
        Queue::forward(array_filter(config('monica.queues'), filled(...)));
    }

    /**
     * Turbo keeps the page loaded between visits, so a deployment that changes the bundle has to
     * be told apart from an ordinary visit: a page naming other assets is loaded in full.
     */
    private function configureAssets(): void
    {
        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);
    }

    /**
     * A contact saved five times in a minute is sent to the search engine once: a sync already
     * waiting on the queue for the same records absorbs the next. The database engine searches
     * what a record says, written to its own columns, rather than its raw columns.
     */
    private function configureSearch(): void
    {
        Scout::makeSearchableUsing(MakeSearchableUniquely::class);
        Scout::removeFromSearchUsing(RemoveFromSearchUniquely::class);

        $this->app->make(EngineManager::class)->extend('database', fn (): MaterializedDatabaseEngine => new MaterializedDatabaseEngine);
    }

    /**
     * A flag is declared by its class in `app/Features`. Registering every class here lets the admin
     * screen and `monica:list-flags` name a flag before anybody has been asked about it.
     */
    private function configureFlags(): void
    {
        Feature::discover();
    }

    /**
     * A deactivated person is refused at every door, not only at the sign in screen: their API keys,
     * the devices syncing over CardDAV and the assistants they connected all stop answering until an
     * administrator reactivates them. Nothing is revoked, so reactivating brings every one of them back.
     */
    private function refuseDeactivatedUsers(): void
    {
        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $key, bool $isValid): bool => $isValid && $key->tokenable instanceof User && $key->tokenable->is_active,
        );

        Auth::provider('active-users', fn (Application $app, array $config): EloquentUserProvider => new EloquentUserProvider($app['hash'], $config['model'])
            ->withQuery(fn (Builder $query): Builder => $query->where('is_active', true)));
    }

    /**
     * Passport exists only so an assistant can reach the MCP server through OAuth. An access token
     * lives a day and is renewed through its refresh token, so a revoked assistant loses access
     * within a day even when it holds on to what it was given.
     */
    private function configurePassport(): void
    {
        Passport::tokensExpireIn(CarbonInterval::day());
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));

        Passport::authorizationView(fn (array $parameters): Response => response()->view('app.auth.oauth-authorize', [
            'viewModel' => new OAuthAuthorizationViewModel(
                client: $parameters['client'],
                authToken: $parameters['authToken'],
                user: $parameters['user'],
                redirectUri: $parameters['request']->string('redirect_uri')->toString(),
                scopes: $parameters['scopes'],
            ),
        ]));
        // English on purpose: translating here would load a language file on every request, before
        // the reader's language is known. The authorization screen translates it.
        Passport::tokensCan([
            'mcp' => 'Use MCP server',
        ]);
    }

    private function configureBilling(): void
    {
        Cashier::useCustomerModel(Account::class);
        Cashier::keepPastDueSubscriptionsActive();
        Cashier::calculateTaxes();
    }

    private function forceSecureUrls(): void
    {
        if ($this->app->environment('local', 'testing')) {
            return;
        }

        URL::forceScheme('https');
    }

    /**
     * Cloudflare's address ranges join the proxies the deployment already trusts rather than
     * replace them, so a balancer standing between Cloudflare and the application stays trusted.
     */
    private function configureTrustedProxies(): void
    {
        LaravelCloudflare::getProxiesUsing(function (): array {
            $configuredProxies = (string) config('trustedproxy.proxies');

            if (in_array($configuredProxies, ['*', '**'], true)) {
                return [...CloudflareProxies::load(), '0.0.0.0/0', '::/0'];
            }

            return [...CloudflareProxies::load(), ...array_filter(array_map(trim(...), explode(',', $configuredProxies)))];
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('mcp', function (Request $request): Limit {
            if (config('monica.self_hosted')) {
                return Limit::none();
            }

            $token = $request->user()?->currentAccessToken();

            if ($token instanceof AccessToken) {
                return Limit::perMinute(60)->by("oauth:{$token->oauth_access_token_id}");
            }

            return Limit::perMinute(60)
                ->by((string) ($token?->getKey() ?: $request->ip()));
        });
        RateLimiter::for('passkey', fn (Request $request): Limit => Limit::perMinute(self::MAX_LOGIN_ATTEMPTS)
            ->by($request->string('credential.id')->lower()->toString().'|'.$request->ip()));
        RateLimiter::for('confirm-password', fn (Request $request): Limit => Limit::perMinute(self::MAX_LOGIN_ATTEMPTS)
            ->by($request->user()?->id ?: $request->ip()));
    }

    private function configureCollation(): void
    {
        if (Collection::hasMacro('sortByCollator')) {
            return;
        }

        Collection::macro('sortByCollator', function (callable|string $callback, bool $descending = false): Collection {
            /** @var Collection<array-key, mixed> $collect */
            $collect = $this;

            return LocaleCollation::sortByCollator($collect, $callback, $descending);
        });
    }

    /** @codeCoverageIgnore */
    private function configureDefaultsProduction(): void
    {
        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
