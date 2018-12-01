<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry dsn
    |--------------------------------------------------------------------------
    |
    | Do not put your secret!
    | See https://sentry.io/settings/{slug}/{project}/keys/
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Release number
    |--------------------------------------------------------------------------
    |
    | Stored in .sentry-release file, or get from git commit number
    |
    */
    'release' => is_file(__DIR__.'/../.sentry-release') ? file_get_contents(__DIR__.'/../.sentry-release') : (is_dir(__DIR__.'/../.git') ? trim(exec('git log --pretty="%h" -n1 HEAD')) : null),

    /*
    |--------------------------------------------------------------------------
    | Capture bindings on SQL queries
    |--------------------------------------------------------------------------
    */
    'breadcrumbs.sql_bindings' => true,

    /*
    |--------------------------------------------------------------------------
    | Capture default user context
    |--------------------------------------------------------------------------
    */
    'user_context' => true,

    /*
    |--------------------------------------------------------------------------
    | Auth token for api calls
    |--------------------------------------------------------------------------
    |
    | See https://sentry.io/settings/account/api/auth-tokens/
    |
    */
    'auth_token' => env('SENTRY_AUTH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Organisation slug
    |--------------------------------------------------------------------------
    */
    'organisation' => env('SENTRY_ORG'),

    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    */
    'project' => env('SENTRY_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Git repository set in sentry
    |--------------------------------------------------------------------------
    |
    | See https://sentry.io/settings/{slug}/repos/
    |
    */
    'repo' => env('SENTRY_REPO', 'monicahq/monica'),
];
