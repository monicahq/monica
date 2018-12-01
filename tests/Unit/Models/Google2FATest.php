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

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Session\Store;
use App\Http\Requests\Request;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class Google2FATest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGoogle2faWrongKey()
    {
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey(32);

        $result = $google2fa->verifyGoogle2FA($secret, 'aaaaaa');

        $this->assertEquals(false, $result);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGoogle2faGoodKey()
    {
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey(32);
        $one_time_password = $google2fa->getCurrentOtp($secret);

        $result = $google2fa->verifyGoogle2FA($secret, $one_time_password);

        $this->assertEquals(true, $result);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGoogle2faLogin()
    {
        config(['google2fa.enabled' => true]);

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey(32);

        $user = factory(User::class)->create();
        $user->google2fa_secret = $secret;
        $this->actingAs($user);

        $request = $this->app['request'];
        // Avoid "Session store not set on request." - Exception!
        $request->setLaravelSession(new Store('test', new NullSessionHandler));
        $request->getSession()->start();

        $authenticator = new \PragmaRX\Google2FALaravel\Support\Authenticator($request);

        $this->assertEquals(false, $authenticator->isAuthenticated());

        $this->assertEquals(true, $google2fa->isActivated());

        $google2fa->login();

        $this->assertEquals(true, $authenticator->isAuthenticated());
    }
}
