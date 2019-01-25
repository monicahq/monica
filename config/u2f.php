<?php

return [
    /*
     * Enable the u2f middleware, if false the middleware will not redirect to the u2f authentication page
     */
    'enable' => env('MFA_ENABLED', env('2FA_ENABLED', false)),

    /*
     * Do not redirect user without u2f key to the u2f authentication page after login
     */
    'byPassUserWithoutKey' => true,

    /*
     * The sessionU2fName attribut will be set to true when the user validate an u2f
     */
    'sessionU2fName' => 'u2f_auth',

    /*
     * Controller configuration
     */
    'register' => [
        /*
         * the template to load for the registration page
         */
        'view' => 'settings.security.u2f-enable',

        /*
         * the route to redirect after a successful key registration (default /)
         */
        'postSuccessRedirectRoute' => '',

    ],

    'authenticate' => [
        /*
         * the template to load for the authentication page
         */
        'view' => 'auth.validateu2f',

        /*
         * the route to redirect after a successful key authentication (default /)
         */
        'postSuccessRedirectRoute' => '',
    ],

    /*
     * The authenticate middleware. If the request is valid for this middleware we
     * can get the current uer by Auth::user()
     */
    'authMiddlewareName' => 'web', // web needs to come first, then auth, for sessions to work properly.
];
