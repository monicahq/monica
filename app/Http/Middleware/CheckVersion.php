<?php

namespace App\Http\Middleware;

use Closure;
use PharIo\Version\Version;
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
        if (($version = config('monica.app_version')) !== '') {
            $instance = Instance::first();

            $appVersion = new Version($version);
            $latestVersion = new Version($instance->latest_version ?? '0.0.0');
            $currentVersion = new Version($instance->current_version ?? '0.0.0');

            if ($latestVersion == $appVersion && $currentVersion != $latestVersion) {

                // The instance has been updated to the latest version. We reset
                // the ping data.

                $instance->current_version = $instance->latest_version;
                $instance->latest_release_notes = null;
                $instance->number_of_versions_since_current_version = null;
                $instance->save();
            }
        }

        return $next($request);
    }
}
