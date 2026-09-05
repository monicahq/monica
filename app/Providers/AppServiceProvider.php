<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\VerifyPasskey;
use App\Helpers\LocaleCollation;
use App\Http\Responses\PasskeyDeletedResponse;
use App\Http\Responses\PasskeyLoginResponse;
use App\Models\Passkey;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Passkeys\Passkeys;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * How many sign-in attempts one credential may make from one place
     * before it is refused for a while. The same number the password flow uses,
     * so there is one answer to how many tries somebody gets.
     */
    private const int MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->configurePasskeys();
    }

    /**
     * Point the passkey package at this application's own pieces.
     *
     * The model is ours so that a passkey somebody does not own is a 404 rather
     * than the package's 403. `VerifyPasskey` is ours so that a deactivated user
     * is refused before the signature is looked at, which the package's own
     * authorisation callback happens too late to do. The two responses are ours
     * because the package redirects to a fixed path from config, and this
     * application redirects to where the person was going.
     */
    private function configurePasskeys(): void
    {
        Passkeys::usePasskeyModel(Passkey::class);
        Passkeys::useUserModel(User::class);

        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        $this->forceSecureUrls();
        $this->configureRateLimiting();
        $this->configureCollation();
        $this->configureDefaultsProduction();
    }

    /**
     * A passkey is refused outright by the browser over a connection that is not
     * secure, so every generated address has to be https wherever the product
     * actually runs. Local and test runs are left alone: localhost is the
     * exception browsers already make, and forcing the scheme under test would
     * only make every asserted address wrong.
     */
    private function forceSecureUrls(): void
    {
        if ($this->app->environment('local', 'testing')) {
            return;
        }

        URL::forceScheme('https');
    }

    /**
     * The limiter the passkey routes are throttled by, named in
     * config/passkeys.php.
     *
     * It exists because the package ships `throttle:6,1`, which keys on the
     * address alone, so everybody behind one address would share a single
     * allowance. Keying on the credential instead means one passkey being
     * hammered cannot lock out the person at the next desk.
     *
     * The options request carries no credential yet, so it falls back to the
     * address, which is the only thing there is to count at that point.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('passkey', fn (Request $request): Limit => Limit::perMinute(self::MAX_LOGIN_ATTEMPTS)
            ->by($request->string('credential.id')->lower()->toString().'|'.$request->ip()));
    }

    /**
     * How the application orders a list it is holding whole by the reader's
     * language. Everything outside `LocaleCollation` reaches it this way rather
     * than through the helper, so a call site reads as ordinary collection work
     * and says nothing about ICU.
     */
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

    /**
     * Configure default behaviors for production-ready applications.
     *
     * @codeCoverageIgnore
     */
    private function configureDefaultsProduction(): void
    {
        // Enable strict mode for Eloquent models in non-production environments
        // TODO: fix failing code related to `preventAccessingMissingAttributes`
        // Model::shouldBeStrict(! app()->isProduction());

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
