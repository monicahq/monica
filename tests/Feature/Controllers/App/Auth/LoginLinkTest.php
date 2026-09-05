<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LoginLink\Exceptions\DidNotFindUserToLogIn;
use Spatie\LoginLink\Exceptions\NotAllowedInCurrentEnvironment;
use Tests\TestCase;

class LoginLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_shortcut_outside_the_allowed_environments(): void
    {
        User::factory()->create(['email' => ExampleAccountSeeder::EMAIL]);

        $response = $this->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertDontSee('Sign in as');
    }

    #[Test]
    public function it_shows_the_shortcut_in_an_allowed_environment(): void
    {
        config(['login-link.allowed_environments' => ['testing']]);

        User::factory()->create(['email' => ExampleAccountSeeder::EMAIL]);

        $response = $this->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertSee('Sign in as Monica Geller');
        $response->assertSee(ExampleAccountSeeder::EMAIL);
    }

    #[Test]
    public function it_hides_the_shortcut_when_the_example_account_was_never_seeded(): void
    {
        config(['login-link.allowed_environments' => ['testing']]);

        User::factory()->create(['email' => 'gunther@centralperk.example']);

        $response = $this->get(route('auth.login.new'));

        $response->assertStatus(200);
        $response->assertDontSee('Sign in as');
    }

    #[Test]
    public function it_signs_the_seeded_account_in(): void
    {
        config(['login-link.allowed_environments' => ['testing']]);

        $user = User::factory()->create(['email' => ExampleAccountSeeder::EMAIL]);

        $response = $this->post(route('loginLinkLogin'), [
            'email' => ExampleAccountSeeder::EMAIL,
            'redirect_url' => route('home.index'),
        ]);

        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function it_refuses_to_sign_anybody_in_outside_the_allowed_environments(): void
    {
        User::factory()->create(['email' => ExampleAccountSeeder::EMAIL]);

        $this->withoutExceptionHandling();
        $this->expectException(NotAllowedInCurrentEnvironment::class);

        $this->post(route('loginLinkLogin'), [
            'email' => ExampleAccountSeeder::EMAIL,
        ]);
    }

    #[Test]
    public function it_refuses_an_email_address_that_belongs_to_nobody(): void
    {
        config(['login-link.allowed_environments' => ['testing']]);

        $this->withoutExceptionHandling();
        $this->expectException(DidNotFindUserToLogIn::class);

        $this->post(route('loginLinkLogin'), [
            'email' => 'gunther@centralperk.example',
        ]);
    }
}
