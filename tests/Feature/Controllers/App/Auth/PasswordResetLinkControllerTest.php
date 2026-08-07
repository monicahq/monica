<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_page_that_asks_for_a_reset_link(): void
    {
        $response = $this->get(route('auth.password.new'));

        $response->assertStatus(200);
        $response->assertSee('Forgot your password?');
    }

    #[Test]
    public function it_sends_a_reset_link(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->from(route('auth.password.new'))
            ->post(route('auth.password.create'), ['email' => 'michael.scott@dundermifflin.com']);

        $response->assertRedirect(route('auth.password.new'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_says_the_same_thing_when_the_address_has_no_account(): void
    {
        Notification::fake();

        $response = $this->from(route('auth.password.new'))
            ->post(route('auth.password.create'), ['email' => 'nobody@dundermifflin.com']);

        // Identical to the answer above, so this form cannot be used to find
        // out who has an account here.
        $response->assertRedirect(route('auth.password.new'));
        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    #[Test]
    public function it_refuses_something_that_is_not_an_address(): void
    {
        $response = $this->post(route('auth.password.create'), ['email' => 'that is not an email']);

        $response->assertSessionHasErrors('email');
    }
}
