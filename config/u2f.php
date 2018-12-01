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
