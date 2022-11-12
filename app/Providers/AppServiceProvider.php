<?php

namespace App\Providers;

use App\Http\Controllers\Profile\WebauthnDestroyResponse;
use App\Http\Controllers\Profile\WebauthnUpdateResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Knuckles\Scribe\Scribe;
use Laravel\Sanctum\Sanctum;
use LaravelWebauthn\Facades\Webauthn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
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

        Scribe::beforeResponseCall(function () {
            // @codeCoverageIgnoreStart
            Carbon::setTestNow(Carbon::create(2020, 1, 1, 0, 0, 0, 'UTC'));
            Artisan::call('monica:dummy', [
                '--migrate' => true,
                '--force' => true,
            ]);
            $user = User::first();
            Sanctum::actingAs($user, ['*']);
            // @codeCoverageIgnoreEnd
        });
    }
}
