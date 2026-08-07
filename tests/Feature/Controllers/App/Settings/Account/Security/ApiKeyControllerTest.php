<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dundie awards bot',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your API key is ready.');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    /**
     * The key rides back on the session so the screen can print it, and is gone
     * by the next request.
     */
    #[Test]
    public function it_hands_the_key_over_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dundie awards bot',
        ]);

        $response->assertSessionHas('apiKey');

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertStatus(200)
            ->assertSee('Your new API key', escape: false);

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertDontSee('Your new API key', escape: false);
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_short(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'ab',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_refuses_a_missing_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), []);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_revokes_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        $response = $this->actingAs($user)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'The API key is revoked.');

        $this->assertModelMissing($apiKey);
    }

    #[Test]
    public function it_refuses_to_revoke_a_key_that_belongs_to_somebody_else(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $michael = User::factory()->create(['company_id' => $company->id]);
        $dwight = User::factory()->create(['company_id' => $company->id]);
        $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

        $response = $this->actingAs($michael)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertNotFound();

        $this->assertModelExists($apiKey);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $this->post(route('settings.apiKeys.create'), ['name' => 'Dundie awards bot'])
            ->assertRedirect(route('auth.login.new'));

        $this->delete(route('settings.apiKeys.destroy', 1))
            ->assertRedirect(route('auth.login.new'));
    }
}
