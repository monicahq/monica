<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
            'password' => 'a-very-strong-password',
            'password_confirmation' => 'a-very-strong-password',
            'device_name' => 'Dundie awards bot',
            'terms' => true,
        ], $overrides);
    }

    #[Test]
    public function it_creates_an_account_and_hands_back_a_token(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload());

        $response->assertCreated();
        $response->assertJsonStructure(['token', 'expires_at']);
        $response->assertJsonPath('expires_at', null);

        $this->assertDatabaseHas('users', [
            'email' => 'michael.scott@dundermifflin.com',
            'first_name' => 'Michael',
        ]);
    }

    #[Test]
    public function it_stores_an_email_address_typed_with_capitals_in_lowercase(): void
    {
        Queue::fake();

        $this->json('POST', '/api/v1/register', $this->payload([
            'email' => 'Michael.Scott@DunderMifflin.com',
        ]))->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'michael.scott@dundermifflin.com']);
    }

    #[Test]
    public function it_hands_the_token_back_as_a_string_and_nothing_else(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload());

        $response->assertCreated();
        $this->assertIsString($response->json('token'));
        $this->assertSame(['token', 'expires_at'], array_keys($response->json()));
    }

    #[Test]
    public function it_builds_the_account_the_way_the_sign_up_screen_does(): void
    {
        Queue::fake();

        $this->json('POST', '/api/v1/register', $this->payload())->assertCreated();

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertEquals(1, $user->account->vaults()->count());
    }

    #[Test]
    public function it_names_the_key_after_the_device(): void
    {
        Queue::fake();

        $this->json('POST', '/api/v1/register', $this->payload())->assertCreated();

        $user = User::query()->where('email', 'michael.scott@dundermifflin.com')->firstOrFail();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_refuses_an_address_somebody_already_holds(): void
    {
        Queue::fake();

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $response = $this->json('POST', '/api/v1/register', $this->payload());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_refuses_a_throwaway_address(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload([
            'email' => 'michael.scott@mailinator.com',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_refuses_a_password_that_is_too_short(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function it_refuses_a_password_that_was_not_repeated(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload([
            'password_confirmation' => 'something-else-entirely',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function it_refuses_to_open_an_account_without_the_terms(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload(['terms' => false]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['terms']);
        $this->assertDatabaseMissing('users', ['email' => 'michael.scott@dundermifflin.com']);
    }

    #[Test]
    public function it_validates_the_required_fields(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'terms']);
    }

    #[Test]
    public function it_never_echoes_the_password_back(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', $this->payload());

        $response->assertCreated();
        $this->assertStringNotContainsString('a-very-strong-password', $response->getContent());
    }

    #[Test]
    public function it_answers_not_found_while_registration_is_disabled(): void
    {
        config()->set('monica.registration_enabled', false);

        $response = $this->json('POST', '/api/v1/register', $this->payload());

        $response->assertNotFound();
        $this->assertSame(0, User::query()->count());
    }
}
