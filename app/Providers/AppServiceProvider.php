<?php

namespace App\Providers;

use App\Http\Controllers\Profile\WebauthnDestroyResponse;
use App\Http\Controllers\Profile\WebauthnUpdateResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use LaravelWebauthn\Facades\Webauthn;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
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
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
            $environment->addExtension(new ExternalLinkExtension());

            $converter = new MarkdownConverter($environment);

            $result = (string) $converter->convert($string);
            $result = ltrim($result, '<p>');
            $result = rtrim($result, '</p>');

            return $result;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('oauth2-socialite', function (Request $request) {
            return Limit::perMinute(5)->by(optional($request->user())->id ?: $request->ip());
        });

        Webauthn::updateViewResponseUsing(WebauthnUpdateResponse::class);
        Webauthn::destroyViewResponseUsing(WebauthnDestroyResponse::class);
    }
}
