<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\RequestHelper;

class SentryContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (app()->bound('sentry') && config('monica.sentry_support')) {
            /** @var \Raven_Client $sentry */
            $sentry = app('sentry');

            $user_context = [];
            $extra_context = [];

            // Add user context
            if (auth()->check()) {
                $user = auth()->user();

                $user_context['id'] = $user->id;
                $user_context['username'] = $user->name;
                $user_context['email'] = $user->email;
                $extra_context['isSubscribed'] = $user->account->isSubscribed();
            } else {
                $user_context['id'] = null;
                $user_context['ip_address'] = RequestHelper::ip();
            }

            $sentry->user_context($user_context);
            $sentry->extra_context($extra_context);
        }

        return $next($request);
    }
}
