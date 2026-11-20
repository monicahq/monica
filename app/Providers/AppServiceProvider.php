<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\VerifyPasskey;
use App\Enums\SsoProviderEnum;
use App\Helpers\FlagState;
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
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
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
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\RobotsRule;
use Laravel\Head\ErrorPages;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Laravel\Mcp\Server\Registrar;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\Command;
use Laravel\Nightwatch\Records\Exception as ExceptionRecord;
use Laravel\Nightwatch\Records\OutgoingRequest;
use Laravel\Nightwatch\Records\Query;
use Laravel\Nightwatch\Records\Request as RequestRecord;
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
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    private const int MAX_LOGIN_ATTEMPTS = 5;

    private const string FRAMEWORK_TABLE_QUERY = '/\b(?:from|into|update) [`"]?(?:sessions|cache|cache_locks|jobs|job_batches|failed_jobs|telescope_\w+)\b/';

    private const string TELEGRAM_BOT_TOKEN = '#bot\d+:[A-Za-z0-9_-]+#';

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
        $this->configurePassport();
        $this->configureSearch();
        $this->configureFlags();
        $this->configureQueues();
        $this->configureAssets();
        $this->configureHead();
        $this->configureNightwatch();
        $this->configureSignInWithApple();
        $this->refuseDeactivatedUsers();
    }

    /**
     * Apple's package is suggested rather than required, so it is told about only
     * when present. Its key is a PEM file of several lines, and an environment
     * variable or a field of the admin space holds one: the key is put back in
     * shape before the package reads it.
     */
    private function configureSignInWithApple(): void
    {
        if (! in_array(SsoProviderEnum::Apple->value, config('monica.sso_installed', []), true)) {
            return;
        }

        Event::listen(function (SocialiteWasCalled $event): void {
            config()->set('services.apple.private_key', $this->applePrivateKeyAsPem((string) config('services.apple.private_key')));

            $event->extendSocialite(SsoProviderEnum::Apple->value, AppleProvider::class);
        });
    }

    private function applePrivateKeyAsPem(string $key): string
    {
        if ($key === '' || is_file($key)) {
            return $key;
        }

        $body = (string) preg_replace('/-----[A-Z ]+-----|\\\\n|\s/', '', $key);

        return "-----BEGIN PRIVATE KEY-----\n".chunk_split($body, 64, "\n")."-----END PRIVATE KEY-----\n";
    }

    /**
     * Every page asks search engines to stay away unless it says otherwise, which only the public
     * pages do, through the `searchable` middleware. An error page stays away even under them.
     */
    private function configureHead(): void
    {
        Head::defaults(function (HeadBuilder $head): void {
            $head->title(config('app.name'), exact: true)
                ->robots(RobotsRule::NoIndex)
                ->og(type: OgType::Website, siteName: config('app.name'));
        });

        Head::errors(function (ErrorPages $errors): void {
            $errors->defaults(robots: RobotsRule::NoIndex);
        });
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

        // The flags a request used are its own: a job it queues starts without them and names its own.
        Context::dehydrating(fn (Repository $context) => $context->forget('flags'));
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

        Passport::authorizationView(function (array $parameters): Response {
            abort_unless(FlagState::isOn('mcp-server', $parameters['user']->account), 404);

            return response()->view('app.auth.oauth-authorize', [
                'viewModel' => new OAuthAuthorizationViewModel(
                    client: $parameters['client'],
                    authToken: $parameters['authToken'],
                    user: $parameters['user'],
                    redirectUri: $parameters['request']->string('redirect_uri')->toString(),
                    scopes: $parameters['scopes'],
                ),
            ]);
        });
        // English on purpose: translating here would load a language file on every request, before
        // the reader's language is known. The authorization screen translates it.
        Passport::tokensCan([
            Registrar::OAUTH_SCOPE => 'Use MCP server',
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
            $perMinute = (int) config('monica.mcp_requests_per_minute');

            if ($token instanceof AccessToken) {
                return Limit::perMinute($perMinute)->by("oauth:{$token->oauth_access_token_id}");
            }

            return Limit::perMinute($perMinute)
                ->by($token instanceof PersonalAccessToken ? "key:{$token->getKey()}" : (string) $request->ip());
        });
        RateLimiter::for('support', fn (Request $request): array => [
            Limit::perMinute(5)->by("support-minute:{$request->user()?->id}"),
            Limit::perDay(50)->by("support-day:{$request->user()?->id}"),
        ]);
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

    /**
     * A trace carries what explains a request and nothing that names somebody. The session,
     * cache and queue tables are the framework's own traffic, a login throttle key holds an
     * address, and a link token, a signature, a search term, the Telegram bot token, an
     * address on a command line or the visitor's network address, in its own field or in a
     * header a proxy adds, would otherwise leave with the trace. Who made a request is
     * sent as an id, and the health check opens no trace at all.
     */
    private function configureNightwatch(): void
    {
        Nightwatch::user(fn (Authenticatable $user): array => ['id' => $user->getAuthIdentifier()]);

        Event::listen(DiagnosingHealth::class, fn () => Nightwatch::dontSample());

        Nightwatch::rejectQueries(fn (Query $query): bool => preg_match(self::FRAMEWORK_TABLE_QUERY, $query->sql) === 1);

        Nightwatch::rejectCacheKeys(['/@/']);

        Nightwatch::redactRequests(function (RequestRecord $request): void {
            $request->url = preg_replace(
                ['/[A-Za-z0-9]{64}/', '/([?&](?:code|query|signature)=)[^&]*/'],
                ['***', '$1***'],
                $request->url,
            ) ?? $request->url;
            $request->headers->remove('X-Telegram-Bot-Api-Secret-Token');
            $request->ip = '';
            $request->headers->remove('X-Forwarded-For');
            $request->headers->remove('X-Real-IP');
            $request->headers->remove('Forwarded');
            $request->headers->remove('CF-Connecting-IP');
            $request->headers->remove('True-Client-IP');
        });

        Nightwatch::redactOutgoingRequests(function (OutgoingRequest $outgoingRequest): void {
            $outgoingRequest->url = preg_replace(self::TELEGRAM_BOT_TOKEN, 'bot***', $outgoingRequest->url) ?? $outgoingRequest->url;
        });

        Nightwatch::redactExceptions(function (ExceptionRecord $exception): void {
            $exception->message = preg_replace(self::TELEGRAM_BOT_TOKEN, 'bot***', $exception->message) ?? $exception->message;
        });

        Nightwatch::redactCommands(function (Command $command): void {
            $command->command = preg_replace('/[^\s=]+@\S+/', '***', $command->command) ?? $command->command;
        });
    }
}
