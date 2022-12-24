<?php

namespace App\Providers;

use App\Http\Controllers\Profile\WebauthnDestroyResponse;
use App\Http\Controllers\Profile\WebauthnUpdateResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use LaravelWebauthn\Facades\Webauthn;
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
