<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\RequestHelper;
use App\Models\Contact\Contact;
use Illuminate\Http\JsonResponse;
use App\Services\Instance\IdHasher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use App\Exceptions\WrongIdException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

        if (Config::get('app.force_url')) {
            URL::forceRootUrl(Str::of(config('app.url'))->ltrim('/'));
        }

        if (App::environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();

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
     */
    public function map(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace.'\Api')
            ->group(base_path('routes/api.php'));

        Route::prefix('oauth')
            ->namespace($this->namespace.'\Api')
            ->group(base_path('routes/oauth.php'));

        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));

        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/special.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('monica.rate_limit_api'))
                ->by(optional($request->user())->id ?: RequestHelper::ip())
                ->response(function (Request $request, array $headers) {
                    $message = [
                        'error' => [
                            'message' => config('api.error_codes.34'),
                            'error_code' => 34,
                        ],
                    ];

                    return new JsonResponse($message, 429, $headers);
                });
        });
        RateLimiter::for('oauth', function (Request $request) {
            return Limit::perMinute(config('monica.rate_limit_oauth'))->by($request->input('email') ?: RequestHelper::ip());
        });
    }
}
