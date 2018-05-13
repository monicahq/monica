<?php

namespace App\Providers;

use App\Day;
use App\Pet;
use App\Debt;
use App\Gift;
use App\Note;
use App\Task;
use App\Gender;
use App\Module;
use App\Contact;
use App\Activity;
use App\Reminder;
use App\ContactField;
use App\Relationship;
use App\ReminderRule;
use App\Helpers\IdHasher;
use Illuminate\Routing\Router;
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
            try {
                $value = app('idhasher')->decodeId($value);

                return Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $value)
                    ->firstOrFail();
            } catch (ModelNotFoundException $ex) {
                redirect('/people/notfound')->send();
            }
        });

        Route::bind('contactfield', function ($value, $route) {
            $value = app('idhasher')->decodeId($value);

            return ContactField::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $value)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('activity', function ($value, $route) {
            $value = app('idhasher')->decodeId($value);

            return  Activity::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('reminder', function ($value, $route) {
            $value = app('idhasher')->decodeId($value);

            return  Reminder::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $route->parameter('contact')->id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('task', function ($value, $route) {
            return  Task::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $route->parameter('contact')->id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('gift', function ($value, $route) {
            return  Gift::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $route->parameter('contact')->id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('debt', function ($value, $route) {
            $value = app('idhasher')->decodeId($value);

            return  Debt::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $route->parameter('contact')->id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('relationships', function ($value, $route) {
            Contact::findOrFail($route->parameter('contact')->id);

            $value = app('idhasher')->decodeId($value);

            Relationship::where('account_id', auth()->user()->account_id)
                ->where('contact_is', $route->parameter('contact')->id)
                ->where('of_contact', $value)
                ->firstOrFail();

            return Contact::findOrFail($value);
        });

        Route::bind('note', function ($value, $route) {
            return  Note::where('account_id', auth()->user()->account_id)
                ->where('contact_id', $route->parameter('contact')->id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('journalEntry', function ($value, $route) {
            return  JournalEntry::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('day', function ($value, $route) {
            return  Day::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('pet', function ($value, $route) {
            return Pet::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('gender', function ($value) {
            return Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('reminderRule', function ($value) {
            return ReminderRule::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });

        Route::bind('module', function ($value) {
            return Module::where('account_id', auth()->user()->account_id)
                ->where('id', $value)
                ->firstOrFail();
        });
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
        //
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
}
