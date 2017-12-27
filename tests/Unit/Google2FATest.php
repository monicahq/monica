<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use Illuminate\Session\Store;
use App\Http\Requests\Request;
use Illuminate\Session\NullSessionHandler;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class Google2FATest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGoogle2faWrongKey()
    {
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey(32);

        $authenticator = new Authenticator(request());

        $result = $authenticator->verifyGoogle2FA($secret, 'aaaaaa');

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

        $authenticator = new Authenticator(request());

        $result = $authenticator->verifyGoogle2FA($secret, $one_time_password);

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

        $authenticator = new Authenticator($request);

        $this->assertEquals(false, $authenticator->canPassWithoutCheckingOTP());

        $this->assertEquals(true, $authenticator->isActivated());

        $authenticator->login();

        $this->assertEquals(true, $authenticator->canPassWithoutCheckingOTP());
    }
}
