<?php

namespace Tests\Feature;

use App\Models\User\User;
use Tests\FeatureTestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RegisterTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_user_can_register()
    {
        Mail::fake();

        $params = [
            'email' => 'john.mike@doe.com',
            'first_name' => 'john',
            'last_name' => 'doe',
            'password' => 'admin0',
            'password_confirmation' => 'admin0',
            'policy' => 'true',
            'lang' => 'en',
        ];

        $response = $this->post('/register', $params);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'john.mike@doe.com',
        ]);
    }

    public function test_user_cannot_register_twice()
    {
        Mail::fake();

        $user = factory(User::class)->create();

        $params = [
            'email' => $user->email,
            'first_name' => 'john',
            'last_name' => 'doe',
            'password' => 'admin0',
            'password_confirmation' => 'admin0',
            'policy' => 'true',
            'lang' => 'en',
        ];

        $response = $this->post('/register', $params, [
            'HTTP_REFERER' => '/register',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/register');
    }
}
