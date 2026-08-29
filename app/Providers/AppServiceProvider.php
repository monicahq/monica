<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\LocaleCollation;
use App\Http\Middleware\EnsurePasskeyLoginIsAllowed;
use App\Http\Responses\PasskeyLoginViewResponse;
use App\Http\Responses\PasskeyRegisterViewResponse;
use Cose\Algorithm\ManagerFactory as CoseAlgorithmManagerFactory;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Actions\PrepareAuthenticatedSession;
use LaravelWebauthn\Facades\Webauthn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * How many passkey sign-in attempts one credential may make from one place
     * before it is refused for a while. The same number the password flow uses,
     * so there is one answer to how many tries somebody gets.
     */
    private const int MAX_PASSKEY_ATTEMPTS = 5;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        Webauthn::loginViewResponseUsing(PasskeyLoginViewResponse::class);
        Webauthn::registerViewResponseUsing(PasskeyRegisterViewResponse::class);

        $this->refuseTheSignatureAlgorithmNobodyShouldUse();
    }

    /**
     * Leave RS1 out of the algorithms a passkey may be verified with.
     *
     * RS1 is RSASSA-PKCS1-v1_5 with SHA-1, which `web-auth/cose-lib` refuses to
     * instantiate from 4.7 onwards without an explicit acknowledgement that it is
     * insecure. `asbiin/laravel-webauthn` 6.0.0 registers it in its factory
     * unconditionally, so merely leaving it out of
     * `webauthn.public_key_credential_parameters`, which this application already
     * does, is not enough: the factory throws while it is being built.
     *
     * The factory exists so the manager can pick from it, and the manager is
     * generated from the parameters we configure, so registering fourteen
     * algorithms instead of fifteen changes nothing except that a legacy
     * signature scheme nobody here wants is never constructed.
     *
     * This is a workaround for a bug in the package rather than a decision about
     * cryptography, and it comes out when the package stops registering RS1.
     */
    private function refuseTheSignatureAlgorithmNobodyShouldUse(): void
    {
        $this->app->bind(
            CoseAlgorithmManagerFactory::class,
            fn (): CoseAlgorithmManagerFactory => tap(new CoseAlgorithmManagerFactory, function (CoseAlgorithmManagerFactory $factory): void {
                $algorithms = [
                    RSA\RS256::class,
                    RSA\RS384::class,
                    RSA\RS512::class,
                    RSA\PS256::class,
                    RSA\PS384::class,
                    RSA\PS512::class,
                    ECDSA\ES256::class,
                    ECDSA\ES256K::class,
                    ECDSA\ES384::class,
                    ECDSA\ES512::class,
                    EdDSA\Ed256::class,
                    EdDSA\Ed512::class,
                    EdDSA\Ed25519::class,
                    EdDSA\EdDSA::class,
                ];

                foreach ($algorithms as $algorithm) {
                    $factory->add((string) $algorithm::identifier(), new $algorithm);
                }
            }),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forceSecureUrls();
        $this->configureRateLimiting();
        $this->configurePasskeyPipeline();
        $this->configureCollation();
    }

    /**
     * The stages a passkey sign-in goes through.
     *
     * EnsurePasskeyLoginIsAllowed sits before AttemptToAuthenticate on purpose,
     * so a deactivated user or one who signs in through an identity provider is
     * refused before any signature is looked at.
     *
     * The package's own throttling stage is deliberately absent: naming a
     * limiter in config/webauthn.php replaces it with `throttle:login` on the
     * route, because the package's key is the username and the address, and a
     * passkey sign-in sends no username.
     */
    private function configurePasskeyPipeline(): void
    {
        Webauthn::authenticateThrough(fn (): array => [
            EnsurePasskeyLoginIsAllowed::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);
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
     * The limiter the passkey sign-in routes are throttled by, named in
     * config/webauthn.php.
     *
     * It exists because the package's own limiter keys on the username field and
     * the address, and a passkey sign-in sends no username. That key would
     * collapse to the address alone, so everybody behind one address would share
     * a single allowance. Keying on the credential instead means one passkey
     * being hammered cannot lock out the person at the next desk.
     *
     * The options request carries no credential yet, so it falls back to the
     * address, which is the only thing there is to count at that point.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(self::MAX_PASSKEY_ATTEMPTS)
            ->by($request->string('id')->lower()->toString().'|'.$request->ip()));
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
}
