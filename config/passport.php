<?php

declare(strict_types=1);

return [
    /*
     * Middleware run on every route Passport declares, before each route's own. They bind the
     * scopes to the clients allowed to ask for them, keep one session per phone signed in from
     * the mobile apps, and limit how often one address may knock.
     */
    'middleware' => [
        'throttle:oauth',
        'oauth.scopes',
        'oauth.mobile.sessions',
    ],
];
