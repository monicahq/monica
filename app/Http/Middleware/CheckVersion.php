<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Instance\Instance;

class CheckVersion
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
        $instance = Instance::first();

        if ($instance->latest_version == config('monica.app_version')) {

            // The instance has been updated to the latest version. We reset
            // the ping data.

            $instance->current_version = $instance->latest_version;
            $instance->latest_release_notes = null;
            $instance->number_of_versions_since_current_version = null;
            $instance->save();
        }

        return $next($request);
    }
}
