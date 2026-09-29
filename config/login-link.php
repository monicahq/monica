<?php

declare(strict_types=1);

use Spatie\LoginLink\Http\Controllers\LoginLinkController;

return [
    /*
     * Login links will only work in these environments. In all
     * other environments, an exception will be thrown.
     */
    'allowed_environments' => ['local'],

    /*
     * Login links will only work in these hosts. In all
     * other hosts, an exception will be thrown.
     */
    'allowed_hosts' => [
        'localhost',
        '127.0.0.1',
        '*.test',
        '*.github.dev',
    ],

    /*
     * The package will automatically create a user model when trying
     * to log in a user that doesn't exist.
     */
    'automatically_create_missing_users' => false,

    /*
     * The user model that should be logged in. If this is set to `null`
     * we'll take a look at the model used for the `users`
     * provider in config/auth.php
     */
    'user_model' => null,

    /*
     * After a login link is clicked, we'll redirect the user to this route.
     * If it is set to `null`, we'll redirect the user to their last intended/requested url.
     * You can set it to `/`, for making redirect to the root page.
     */
    'redirect_route_name' => null,

    /*
     * The package will register a route that points to this controller. To have fine
     * grained control over what happens when a login link is clicked, you can
     * override this class.
     */
    'login_link_controller' => LoginLinkController::class,

    /*
     * This middleware will be applied on the route
     * that logs in a user via a link.
     */
    'middleware' => ['web'],
];
