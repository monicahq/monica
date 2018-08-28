<?php

namespace App\Providers;

use App\Helpers\IdHasher;
use Illuminate\Routing\Router;
use App\Models\Contact\Contact;
use App\Exceptions\WrongIdException;
use Illuminate\Support\Facades\Route;
use App\Models\Relationship\Relationship;
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

        /*
        //This route is not used
        Route::bind('relationships', function ($value, $route) {
            Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($route->parameter('contact')->id);

            $value = app('idhasher')->decodeId($value);

            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->findOrFail($value);

            Relationship::where('account_id', auth()->user()->account_id)
                ->where('contact_is', $route->parameter('contact')->id)
                ->where('of_contact', $value)
                ->firstOrFail();

            return $contact;
        });
        */
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
            'namespace' => $this->namespace,
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
            'namespace' => $this->namespace,
        ], function ($router) {
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
        ], function ($router) {
            require base_path('routes/special.php');
        });
    }
}
