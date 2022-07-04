<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

class CheckLocale
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected Application $app;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang');

        if (empty($locale)) {
            $locale = $this->getLocale($request);
        }

        $this->app->setLocale($locale ?? $this->app['config']->get('app.locale'));

        return $next($request);
    }

    /**
     * Get the current or default locale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function getLocale(Request $request): ?string
    {
        return ($user = $request->user())
            ? $user->locale
            : $this->app['language.detector']->detect();
    }
}
