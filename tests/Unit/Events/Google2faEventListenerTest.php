<?php

namespace Tests\Unit\Events;

use App\Events\RecoveryLogin;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\FeatureTestCase;

class Google2faEventListenerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->startSession();

        $request = new FakeRequest();
        $request->session = $this->app['session'];

        Google2FA::setRequest($request);
        app('pragmarx.google2fa')->setStateless(false);
    }

    /** @test */
    public function it_listens_u2f_event()
    {
        $user = $this->signIn();
        $user->google2fa_secret = 'x';

        Event::dispatch('u2f.authentication', ['u2fKey' => null, 'user' => $user]);

        $this->assertTrue($this->app['session']->get('google2fa.auth_passed'));
    }

    /** @test */
    public function it_listens_recovery_event()
    {
        $user = $this->signIn();
        $user->google2fa_secret = 'x';

        Event::dispatch(new RecoveryLogin($user));

        $this->assertTrue($this->app['session']->get('google2fa.auth_passed'));
    }

    /** @test */
    public function it_listens_login_remember_event()
    {
        $user = $this->signIn();
        $user->google2fa_secret = 'x';

        $guard = app(AuthManager::class)->guard();
        $this->setPrivateValue($guard, 'viaRemember', true);

        Event::dispatch(new Login('guard', $user, true));

        $this->assertTrue($this->app['session']->get('google2fa.auth_passed'));
    }
}

class FakeRequest
{
    public $session;

    public function session()
    {
        return $this->session;
    }
}
