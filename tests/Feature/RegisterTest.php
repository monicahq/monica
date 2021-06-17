<?php

namespace Tests\Feature;

use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Jobs\SendNewUserAlert;
use App\Notifications\NewUserAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RegisterTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_user_can_register()
    {
        config(['monica.disable_signup' => false]);

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
        config(['monica.disable_signup' => false]);

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

    public function test_it_dispatches_an_email()
    {
        config(['monica.disable_signup' => false]);

        $route = Notification::route('mail', 'test@test.com');
        Notification::fake();

        config(['monica.email_new_user_notification' => 'test@test.com']);

        $user = factory(User::class)->create();

        SendNewUserAlert::dispatch($user);

        Notification::assertSentTo($route, NewUserAlert::class);

        $notifications = Notification::sent($route, NewUserAlert::class);
        $message = $notifications[0]->toMail();

        $this->assertStringContainsString('New registration', $message->subject);
        $this->assertStringContainsString($user->first_name, implode('', $message->introLines));
        $this->assertStringContainsString($user->last_name, implode('', $message->introLines));
        $this->assertStringContainsString($user->email, implode('', $message->introLines));
    }
}
