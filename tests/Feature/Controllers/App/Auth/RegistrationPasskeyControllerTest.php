<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationPasskeyControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'timezone' => 'Europe/Paris',
            'terms' => '1',
        ], $overrides);
    }

    #[Test]
    public function it_creates_an_account_with_no_password_at_all(): void
    {
        Queue::fake();

        $response = $this->post(route('auth.registerPasskey.create'), $this->payload());

        $response->assertRedirect(route('auth.registerPasskey.new'));

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->usesPasswordlessSignIn());
        $this->assertFalse($user->usesSingleSignOn());
    }

    #[Test]
    public function it_signs_the_new_person_in(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload());

        $this->assertAuthenticated();
    }

    #[Test]
    public function it_creates_the_account_and_its_first_vault(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload());

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();
        $this->assertDatabaseHas('accounts', ['id' => $user->account_id]);
        $this->assertTrue($user->account->vaults()->exists());
    }

    #[Test]
    public function it_requires_everything_the_password_route_requires_except_a_password(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload(['first_name' => '']))
            ->assertSessionHasErrors('first_name');

        $this->post(route('auth.registerPasskey.create'), $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        $this->post(route('auth.registerPasskey.create'), $this->payload(['terms' => '']))
            ->assertSessionHasErrors('terms');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function it_refuses_an_email_address_somebody_already_uses(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->post(route('auth.registerPasskey.create'), $this->payload())
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function it_shows_the_step_that_asks_the_device_for_a_credential(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload());

        $response = $this->get(route('auth.registerPasskey.new'));

        $response->assertStatus(200);
        $response->assertSee('One last thing, Michael');
    }

    #[Test]
    public function the_step_confirms_the_passkey_once_it_exists(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('auth.registerPasskey.new'))
            ->assertSee('Your passkey is created.');
    }

    #[Test]
    public function the_step_says_what_reaches_an_account_that_has_neither(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->get(route('auth.registerPasskey.new'))
            ->assertSee('Set a password instead');
    }

    #[Test]
    public function it_refuses_somebody_already_signed_in_from_creating_another_account(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create())
            ->post(route('auth.registerPasskey.create'), $this->payload())
            ->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_answers_not_found_while_registration_is_disabled(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->post(route('auth.registerPasskey.create'), $this->payload())->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'michael.scott@dundermifflin.com']);
        $this->assertGuest();
    }
}
