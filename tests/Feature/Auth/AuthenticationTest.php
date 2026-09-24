<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function login_screen_can_be_rendered()
    {
        $this->withoutVite();

        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    #[Test]
    public function deletion_notice_is_hidden_by_default()
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login', false)
                ->where('showDeletionNotice', false)
            );
    }

    #[Test]
    public function deletion_notice_is_shown_when_enabled()
    {
        $this->withoutVite();
        config(['monica.show_deletion_notice' => true]);

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login', false)
                ->where('showDeletionNotice', true)
            );
    }

    #[Test]
    public function users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/vaults');
    }

    #[Test]
    public function users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
