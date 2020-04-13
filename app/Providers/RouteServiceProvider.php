<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use App\Models\Contact\Contact;
use App\Services\Instance\IdHasher;
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

        if (App::environment('production')) {
            URL::forceScheme('https');
        }

        Route::bind('contact', function ($value) {
            // In case the user is logged out
            if (! Auth::check()) {
                redirect()->route('loginRedirect')->send();

                return;
            }

            try {
                $id = app(IdHasher::class)->decodeId($value);

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
        $this->mapApiRoutes($router);
        $this->mapWebRoutes($router);
        $this->mapOAuthRoutes($router);
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
        ], function () {
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
