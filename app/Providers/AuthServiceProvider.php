<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        $this->registerPolicies();

        Passport::ignoreCsrfToken(in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']));
    }
}
