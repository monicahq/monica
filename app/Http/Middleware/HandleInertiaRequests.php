<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => [
                'user' => ($user = $request->user()) ? [
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'lastname' => $user->last_name,
                    'email' => $user->email,
                    'help_shown' => $user->help_shown,
                    'locale' => $user->locale,
                    'two_factor_enabled' => Features::enabled(Features::twoFactorAuthentication())
                            && ! is_null($user->two_factor_secret),
                ] : null,
            ],
            'help_links' => fn () => config('monica.help_links'),
            'help_url' => fn () => config('monica.help_center_url'),
            'footer' => Str::markdownExternalLink(trans('Version :version — commit [:short](:url).', [
                'version' => config('monica.app_version'),
                'short' => substr(config('monica.commit'), 0, 7),
                'url' => Str::finish(config('monica.repository', 'https://github.com/monicahq/monica/'), '/').'commit/'.config('monica.commit'),
            ]), 'underline text-xs dark:text-gray-100 hover:text-gray-900 hover:dark:text-gray-200'),
        ]);
    }
}
