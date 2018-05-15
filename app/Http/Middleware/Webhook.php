<?php

namespace App\Http\Middleware;

use Closure;

class Webhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();

        $allowed_ips = config('webhook.valid');

        if ($allowed_ips->contains($ip)) {
            // IP is on the whitlist for webhooks
            return $next($request);
        } else {
            // IP in not on the whitlist for webhooks
            abort(403);
        }
    }
}
