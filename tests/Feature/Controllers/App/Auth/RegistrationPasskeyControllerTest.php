<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Http\Middleware\EnsureRegistrationIsEnabled;
use App\Models\PreviewListEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $response->assertCookie(Auth::guard('web')->getRecallerName());

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->usesPasswordlessSignIn());
        $this->assertFalse($user->usesSingleSignOn());
    }

    #[Test]
    public function it_lets_the_new_person_make_their_first_passkey_without_confirming(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload());

        $this->getJson(route('passkey.registration-options'))->assertOk();
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
    public function it_shows_the_step_that_asks_the_device_for_a_credential(): void
    {
        Queue::fake();

        $this->post(route('auth.registerPasskey.create'), $this->payload());

        $response = $this->get(route('auth.registerPasskey.new'));

        $response->assertStatus(200);
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

    #[Test]
    public function it_creates_the_account_of_somebody_admitted_while_registration_is_closed(): void
    {
        Queue::fake();
        config()->set('monica.registration_enabled', false);

        $entry = PreviewListEntry::factory()->admitted('creed-bratton-token')->create(['email' => 'creed.bratton@dundermifflin.com']);

        $response = $this->post(route('auth.registerPasskey.create'), $this->payload(['admission' => 'creed-bratton-token']));

        $response->assertRedirect(route('auth.registerPasskey.new'));

        $user = User::query()->where('email', 'creed.bratton@dundermifflin.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($entry->refresh()->registered_at);
    }

    #[Test]
    public function it_answers_not_found_for_an_admission_that_was_already_used(): void
    {
        config()->set('monica.registration_enabled', false);

        PreviewListEntry::factory()->admitted('creed-bratton-token')->registered()->create();

        $this->post(route('auth.registerPasskey.create'), $this->payload(['admission' => 'creed-bratton-token']))
            ->assertNotFound();

        $this->assertGuest();
    }

    #[Test]
    public function it_answers_not_found_for_an_admission_a_concurrent_sign_up_used(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->withoutMiddleware(EnsureRegistrationIsEnabled::class)
            ->post(route('auth.registerPasskey.create'), $this->payload(['admission' => 'creed-bratton-token']))
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'michael.scott@dundermifflin.com']);
    }
}
