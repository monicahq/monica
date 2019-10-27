<?php

namespace Tests\Unit\Events;

use Tests\FeatureTestCase;
use App\Events\RecoveryLogin;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\Event;
use PragmaRX\Google2FALaravel\Events\LoginSucceeded;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class U2faEventListenerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->startSession();
    }

    public function test_it_listen_recovery_event()
    {
        $user = $this->signIn();
        factory(U2fKey::class)->create([
            'user_id' => $user->id,
        ]);

        Event::dispatch(new RecoveryLogin($user));

        $this->assertTrue($this->app['session']->get('u2f_auth'));
    }

    public function test_it_listen_google2fa_event()
    {
        $user = $this->signIn();
        factory(U2fKey::class)->create([
            'user_id' => $user->id,
        ]);

        Event::dispatch(new LoginSucceeded($user));

        $this->assertTrue($this->app['session']->get('u2f_auth'));
    }

    public function test_it_listen_login_remember_event()
    {
        $user = $this->signIn();
        factory(U2fKey::class)->create([
            'user_id' => $user->id,
        ]);

        $guard = app(AuthManager::class)->guard();
        $this->setPrivateValue($guard, 'viaRemember', true);

        Event::dispatch(new Login('guard', $user, true));

        $this->assertTrue($this->app['session']->get('u2f_auth'));
    }
}
