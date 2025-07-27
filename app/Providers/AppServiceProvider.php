<?php

namespace App\Providers;

use App\Domains\Contact\ManageDocuments\Events\FileDeleted;
use App\Domains\Contact\ManageDocuments\Listeners\DeleteFileInStorage;
use App\Helpers\CollectionHelper;
use App\Http\Controllers\Profile\WebauthnDestroyResponse;
use App\Http\Controllers\Profile\WebauthnUpdateResponse;
use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\Rules\Password;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Listeners\LoginViaRemember;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Facebook\FacebookExtendSocialite;
use SocialiteProviders\GitHub\GitHubExtendSocialite;
use SocialiteProviders\Google\GoogleExtendSocialite;
use SocialiteProviders\Kanidm\KanidmExtendSocialite;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use SocialiteProviders\LinkedIn\LinkedInExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Tests\TestResponseMacros;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (App::environment('testing') && class_exists(TestResponseMacros::class)) {
            TestResponse::mixin(new TestResponseMacros);
        }

        if (! Str::hasMacro('markdownExternalLink')) {
            Str::macro('markdownExternalLink', function (string $string, string $class = 'underline', bool $openInNewWindow = true): string {
                $config = [
                    'external_link' => [
                        'internal_hosts' => config('app.url'),
                        'html_class' => $class,
                        'open_in_new_window' => $openInNewWindow,
                        'nofollow' => '',
                        'noopener' => 'external',
                        'noreferrer' => 'external',
                    ],
                ];
                $environment = new Environment($config);
                $environment->addExtension(new CommonMarkCoreExtension);
                $environment->addExtension(new GithubFlavoredMarkdownExtension);
                $environment->addExtension(new ExternalLinkExtension);

                $converter = new MarkdownConverter($environment);

                $result = (string) $converter->convert($string);
                $result = ltrim($result, '<p>');
                $result = rtrim($result, '</p>');

                return $result;
            });
        }

        if (! Http::hasMacro('getDnsRecord')) {
            Http::macro('getDnsRecord', function (string $hostname, int $type): ?Collection {
                try {
                    if (($entries = \Safe\dns_get_record($hostname, $type)) != null) {
                        return collect($entries);
                    }
                } catch (\Safe\Exceptions\NetworkException) {
                    // ignore
                }

                return null;
            });
        }

        if (! Collection::hasMacro('sortByCollator')) {
            Collection::macro('sortByCollator', function (callable|string $callback) {
                /** @var Collection */
                $collect = $this;

                return CollectionHelper::sortByCollator($collect, $callback);
            });
        }

        if (! Collection::hasMacro('sortByCollatorDesc')) {
            Collection::macro('sortByCollatorDesc', function (callable|string $callback) {
                /** @var Collection */
                $collect = $this;

                return CollectionHelper::sortByCollator($collect, $callback, descending: true);
            });
        }

        Builder::defaultMorphKeyType('uuid');

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (Config::get('app.force_url') === true) {
            URL::forceRootUrl(Str::of(config('app.url'))->ltrim('/'));
            URL::forceScheme('https');
        }

        RedirectIfAuthenticated::redirectUsing(fn () => route('vault.index', absolute: false));

        Password::defaults(function () {
            return $this->app->environment('production')
                // @codeCoverageIgnoreStart
                ? Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                // @codeCoverageIgnoreEnd
                : Password::min(4);
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip()));
        RateLimiter::for('oauth2-socialite', fn (Request $request) => Limit::perMinute(5)->by(optional($request->user())->id ?: $request->ip()));

        Webauthn::updateViewResponseUsing(WebauthnUpdateResponse::class);
        Webauthn::destroyViewResponseUsing(WebauthnDestroyResponse::class);

        Event::subscribe(LoginViaRemember::class);
        Event::listen(FileDeleted::class, DeleteFileInStorage::class);
        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, FacebookExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, GitHubExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, GoogleExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, LinkedInExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, KanidmExtendSocialite::class);
        Event::listen(SocialiteWasCalled::class, KeycloakExtendSocialite::class);

        Vite::prefetch(concurrency: 3);

        Gate::define('viewPulse', fn (User $user) => $user->is_instance_administrator || $this->app->environment('local'));
    }
}
