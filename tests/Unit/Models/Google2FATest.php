<?php

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

    /** @test */
    public function it_tests_a_wrong_key_for_Google2fa()
    {
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey(32);

        $result = $google2fa->verifyGoogle2FA($secret, 'aaaaaa');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_tests_a_correct_key_for_Google2fa()
    {
        $google2fa = app('pragmarx.google2fa');

        $secret = $google2fa->generateSecretKey(32);
        $one_time_password = $google2fa->getCurrentOtp($secret);

        $result = $google2fa->verifyGoogle2FA($secret, $one_time_password);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_logs_in_with_Google2Fa()
    {
        config(['google2fa.enabled' => true]);

        $google2fa = app('pragmarx.google2fa')->setStateless(false);
        $secret = $google2fa->generateSecretKey(32);

        $user = factory(User::class)->create();
        $user->google2fa_secret = $secret;
        $this->actingAs($user);

        $request = $this->app['request'];
        // Avoid "Session store not set on request." - Exception!
        $request->setLaravelSession(new Store('test', new NullSessionHandler));
        $request->getSession()->start();

        $authenticator = new \PragmaRX\Google2FALaravel\Support\Authenticator($request);

        $this->assertFalse($authenticator->isAuthenticated());

        $this->assertTrue($google2fa->isActivated());

        $google2fa->login();

        $this->assertTrue($authenticator->isAuthenticated());
    }
}
