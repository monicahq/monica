<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

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

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dundie awards bot',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your API key is ready.');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_hands_the_key_over_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.apiKeys.create'), [
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
    public function it_prints_the_new_key_once_it_is_made(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['apiKey' => 'dunder-mifflin-infinity'])
            ->get(route('settings.security.index'))
            ->assertStatus(200)
            ->assertSee('dunder-mifflin-infinity');
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_short(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'ab',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_revokes_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        $response = $this->withConfirmedPassword()->actingAs($user)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'The API key is revoked.');

        $this->assertModelMissing($apiKey);
    }

    #[Test]
    public function it_refuses_to_revoke_a_key_that_belongs_to_somebody_else(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

        $response = $this->withConfirmedPassword()->actingAs($michael)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertNotFound();

        $this->assertModelExists($apiKey);
    }

    #[Test]
    public function it_creates_a_key_that_reaches_sensitive_fields_when_the_box_is_ticked(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->withConfirmedPassword()->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dwight’s phone',
            'sensitive_fields' => '1',
        ]);

        $this->assertContains('sensitive-fields', $user->tokens()->first()->abilities);
    }

    #[Test]
    public function it_refuses_the_administration_scope_to_somebody_who_does_not_administer_the_instance(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->withConfirmedPassword()->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dwight’s queue bot',
            'administration' => '1',
        ]);

        $response->assertSessionHasErrors('administration');
        $this->assertSame(0, $user->tokens()->count());
    }
}
