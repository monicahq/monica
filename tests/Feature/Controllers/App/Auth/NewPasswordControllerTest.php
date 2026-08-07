<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_page_that_chooses_a_new_password(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->get(route('auth.password.edit', [
            'token' => Password::createToken($user),
            'email' => 'michael.scott@dundermifflin.com',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Choose a new password');
        $response->assertSee('michael.scott@dundermifflin.com');
    }

    #[Test]
    public function it_changes_the_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $token = Password::createToken($user);

        $response = $this->post(route('auth.password.update'), [
            'token' => $token,
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'bearsbeatsbattlestar',
        ]);

        $response->assertRedirect(route('auth.login.new'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password_hash));
    }

    #[Test]
    public function it_throws_the_token_away_once_it_is_used(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $token = Password::createToken($user);

        $payload = [
            'token' => $token,
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'bearsbeatsbattlestar',
        ];

        $this->post(route('auth.password.update'), $payload);

        $response = $this->post(route('auth.password.update'), $payload);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_refuses_a_token_that_is_not_right(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.password.update'), [
            'token' => str_repeat('a', 64),
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'bearsbeatsbattlestar',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_refuses_a_password_that_is_too_short(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.password.update'), [
            'token' => Password::createToken($user),
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function it_refuses_a_password_that_is_not_confirmed(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->post(route('auth.password.update'), [
            'token' => Password::createToken($user),
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'bearsbeatsbattlestar',
            'password_confirmation' => 'thatswhatshesaid',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
