<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\TwoFactorChallengeView;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->patchFortifyRoutes();

        Fortify::loginView(fn ($request) => (new LoginController)($request));
        Fortify::registerView(fn ($request) => (new RegisterController)($request));
        Fortify::confirmPasswordsUsing(fn ($user, ?string $password = null) => $user->password
            ? app(StatefulGuard::class)->validate([
                'email' => $user->email,
                'password' => $password,
            ])
            : true
        );

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::twoFactorChallengeView(fn () => new TwoFactorChallengeView);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }

    protected function patchFortifyRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $router = $this->app->make(Router::class);
        $routes = $router->getRoutes();
        collect(['create', 'store'])->each(function ($method) use ($routes) {
            if ($route = $routes->getByAction(RegisteredUserController::class.'@'.$method)) {
                $route->middleware('monica.signup_is_enabled');
            }
        });
    }
}
