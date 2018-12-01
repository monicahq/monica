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
     * Auth container binding
     */

    'enabled' => env('MFA_ENABLED', env('2FA_ENABLED', false)),

    /*
     * Lifetime in minutes.
     * In case you need your users to be asked for a new one time passwords from time to time.
     */

    'lifetime' => 0, // 0 = eternal

    /*
     * Renew lifetime at every new request.
     */

    'keep_alive' => true,

    /*
     * Auth container binding
     */

    'auth' => 'auth',

    /*
     * 2FA verified session var
     */

    'session_var' => 'google2fa',

    /*
     * One Time Password request input name
     */
    'otp_input' => 'one_time_password',

    /*
     * One Time Password Window
     */
    'window' => 8,

    /*
     * Forbid user to reuse One Time Passwords.
     */
    'forbid_old_passwords' => false,

    /*
     * User's table column for google2fa secret
     */
    'otp_secret_column' => 'google2fa_secret',

    /*
     * One Time Password View
     */
    'view' => 'auth/validate2fa',

    /*
     * One Time Password error message
     */
    'error_messages' => [
        'wrong_otp' => 'The two factor authentication has failed.',
    ],

];
