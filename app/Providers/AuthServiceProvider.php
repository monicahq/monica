<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use App\Http\Middleware\TokenUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->registerPolicies();

        Passport::routes();
        Passport::ignoreCsrfToken(in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']));

        $this->app['auth']->provider('usertokens', function ($app, $config) {
            $connection = $app['db']->connection($config['connection'] ?? null);

            return new TokenUserProvider($connection, $app['hash'], $config['table']);
        });
    }
}
