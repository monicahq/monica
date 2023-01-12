<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;

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
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => [
                'user' => $request->user() ? [
                    'help_shown' => $request->user()->help_shown,
                ] : null,
            ],
            'help_links' => fn () => config('monica.help_links'),
            'help_url' => fn () => config('monica.help_center_url'),
            'footer' => Str::markdownExternalLink(__('Version :version — commit [:short](:url).', [
                'version' => config('monica.app_version'),
                'short' => substr(config('monica.commit'), 0, 7),
                'url' => 'https://github.com/monicahq/chandler/commit/'.config('monica.commit'),
            ]), 'underline text-xs dark:text-gray-100 hover:text-gray-900 hover:dark:text-gray-200'),
        ]);
    }
}
