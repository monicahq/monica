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
    public function it_validates_the_required_fields(): void
    {
        Queue::fake();

        $response = $this->json('POST', '/api/v1/register', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'terms']);
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
