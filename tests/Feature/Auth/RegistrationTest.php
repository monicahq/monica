<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Helpers\SignupHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Laravel\Jetstream\Jetstream;
use Mockery;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function testAccessToRegistrationPage(): void
    {
        $this->withoutVite();

        $isSignupEnabled = null;
        $this->app->bind(SignupHelper::class, function () use (&$isSignupEnabled) {
            $mock = Mockery::mock(SignupHelper::class)->makePartial();
            $mock->shouldReceive('isEnabled')->andReturn($isSignupEnabled);
            return $mock;
        });

        $isSignupEnabled = true;
        $response = $this->get('/register');
        $response->assertStatus(Response::HTTP_OK);

        $isSignupEnabled = false;
        $response = $this->get('/register');
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $response->assertSeeText('Registration is currently disabled');
    }

    public function testRegistration(): void
    {
        $isSignupEnabled = null;
        $this->app->bind(SignupHelper::class, function () use (&$isSignupEnabled) {
            $mock = Mockery::mock(SignupHelper::class)->makePartial();
            $mock->shouldReceive('isEnabled')->andReturn($isSignupEnabled);
            return $mock;
        });

        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Password$123',
            'password_confirmation' => 'Password$123',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ];

        $isSignupEnabled = false;
        $response = $this->post('/register', $data);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $response->assertSeeText('Registration is currently disabled');

        $isSignupEnabled = true;
        $response = $this->post('/register', $data);
        $response->assertStatus(Response::HTTP_OK);
        $this->assertAuthenticated();
        $response->assertRedirect('/vaults');
    }
}
