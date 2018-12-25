<?php

namespace App\Providers;

use App\Helpers\IdHasher;
use Illuminate\Routing\Router;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use App\Exceptions\WrongIdException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::bind('contact', function ($value) {
            // In case the user is logged out
            if (! Auth::check()) {
                redirect()->route('login')->send();
            }

            try {
                $id = app('idhasher')->decodeId($value);

                return Contact::where('account_id', auth()->user()->account_id)
                    ->findOrFail($id);
            } catch (WrongIdException $ex) {
                redirect()->route('people.missing')->send();
            } catch (ModelNotFoundException $ex) {
                redirect()->route('people.missing')->send();
            }
        });

        Route::model('otherContact', Contact::class);
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map(Router $router)
    {
        if (App::environment('production')) {
            URL::forceScheme('https');
        }

        $this->mapApiRoutes($router);

        $this->mapWebRoutes($router);

        $this->mapOAuthRoutes($router);

        if (config('carddav.enabled')) {
            $this->mapCardDAVRoutes($router);
        }

        $this->mapSpecialRoutes($router);
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function mapWebRoutes(Router $router)
    {
        $router->group([
            'middleware' => 'web',
            'namespace' => $this->namespace,
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the custom oauth routes for the API.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function mapOAuthRoutes(Router $router)
    {
        $router->group([
            'prefix' => 'oauth',
            'namespace' => $this->namespace.'\Api',
        ], function () {
            require base_path('routes/oauth.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes(Router $router)
    {
        $router->group([
            'prefix' => 'api',
            'middleware' => 'api',
            'namespace' => $this->namespace.'\Api',
        ], function () {
            require base_path('routes/api.php');
        });
    }

    /**
     * Define the "carddav" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapCardDAVRoutes(Router $router)
    {
        $router->group([
            'prefix' => 'carddav',
            'middleware' => 'api',
            'namespace' => $this->namespace,
        ], function () {
            require base_path('routes/carddav.php');
        });
    }

    /**
     * Define the "special" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapSpecialRoutes(Router $router)
    {
        $router->group([
            'middleware' => 'web',
            'namespace' => $this->namespace,
        ], function () {
            require base_path('routes/special.php');
        });
    }
}
