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



namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\Validate2faController;

class LoginListener
{
    /**
     * Handle the Illuminate login event.
     *
     * @param  Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        if (Auth::viaRemember()) {
            if (config('google2fa.enabled') && ! empty($event->user->google2fa_secret)) {
                Validate2faController::loginCallback();
            }
            if (config('u2f.enable') && U2fKey::where('user_id', $event->user->getAuthIdentifier())->count() > 0) {
                session([config('u2f.sessionU2fName') => true]);
            }
        }
    }
}
