<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Helpers\SignupHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Laravel\Jetstream\Jetstream;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function registration_screen_can_be_rendered(): void
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

    #[Test]
    public function new_users_can_register(): void
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
        $this->assertAuthenticated();
        $response->assertRedirect('/vaults');
    }
}
