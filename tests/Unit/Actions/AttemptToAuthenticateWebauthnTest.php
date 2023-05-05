<?php

namespace Tests\Unit\Actions;

use App\Actions\AttemptToAuthenticateWebauthn;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Facades\Webauthn;
use Tests\TestCase;

class AttemptToAuthenticateWebauthnTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_get_user_request()
    {
        $user = User::factory()->create();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $result = app(AttemptToAuthenticateWebauthn::class)->handle($request, fn () => 1);

        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function it_throw_failed_login_exception()
    {
        $request = $this->app->make(Request::class);

        Webauthn::shouldReceive('validateAssertion')->andReturn(false);

        $this->expectException(ValidationException::class);

        app(AttemptToAuthenticateWebauthn::class)->handle($request, fn () => 1);
    }

    /**
     * @test
     */
    public function it_fails_with_request()
    {
        $user = User::factory()->create();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        Webauthn::shouldReceive('validateAssertion')->andReturn(false);

        $this->expectException(ValidationException::class);
        app(AttemptToAuthenticateWebauthn::class)->handle($request, fn () => 1);
    }
}
