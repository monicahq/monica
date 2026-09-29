<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\PasswordResetRequested;
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

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->from(route('auth.password.new'))
            ->post(route('auth.password.create'), ['email' => 'michael.scott@dundermifflin.com']);

        $response->assertRedirect(route('auth.password.new'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        Notification::assertSentTo($user, PasswordResetRequested::class);
    }

    #[Test]
    public function it_records_the_email_it_sends(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->from(route('auth.password.new'))
            ->post(route('auth.password.create'), ['email' => 'michael.scott@dundermifflin.com']);

        $response->assertRedirect(route('auth.password.new'));

        $this->assertDatabaseHas('emails_sent', [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'email_type' => EmailType::PasswordResetRequested->value,
            'email_address' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_says_the_same_thing_when_the_address_has_no_account(): void
    {
        Notification::fake();

        $response = $this->from(route('auth.password.new'))
            ->post(route('auth.password.create'), ['email' => 'nobody@dundermifflin.com']);

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
