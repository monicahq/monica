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
    private const int MAX_LOGIN_ATTEMPTS = 5;

    #[Override]
    public function register(): void
    {
        $this->configurePasskeys();
    }

    private function configurePasskeys(): void
    {
        Passkeys::usePasskeyModel(Passkey::class);
        Passkeys::useUserModel(User::class);

        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        $this->forceSecureUrls();
        $this->configureRateLimiting();
        $this->configureCollation();
        $this->configureDefaultsProduction();
    }

    private function forceSecureUrls(): void
    {
        if ($this->app->environment('local', 'testing')) {
            return;
        }

        URL::forceScheme('https');
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('passkey', fn (Request $request): Limit => Limit::perMinute(self::MAX_LOGIN_ATTEMPTS)
            ->by($request->string('credential.id')->lower()->toString().'|'.$request->ip()));
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
