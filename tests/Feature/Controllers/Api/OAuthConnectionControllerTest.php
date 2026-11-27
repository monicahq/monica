<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Client;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthConnectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['client_name', 'label', 'scopes', 'is_current', 'last_used_at', 'created_at', 'updated_at'],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_phones_and_assistants_newest_first(): void
    {
        $user = User::factory()->create();
        $older = OAuthConnection::factory()->create(['user_id' => $user->id, 'label' => 'Pixel 9']);
        $newer = OAuthConnection::factory()->create([
            'user_id' => $user->id,
            'client_id' => Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant'])->id,
            'label' => null,
            'scopes' => ['mcp:use'],
        ]);
        OAuthConnection::factory()->ended()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/oauth-connections');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta']);
        $response->assertJsonPath('data.0.id', (string) $newer->id);
        $response->assertJsonPath('data.1.id', (string) $older->id);
        $response->assertJsonPath('data.0.attributes.is_current', false);
        $response->assertJsonPath('data.0.attributes.client_name', 'Schrute Farms Assistant');
        $response->assertJsonPath('data.0.attributes.scopes', ['mcp:use']);
        $response->assertJsonPath('data.1.attributes.client_name', 'Monica for mobile');
    }

    #[Test]
    public function it_does_not_list_the_connections_of_another_account(): void
    {
        OAuthConnection::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/oauth-connections')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_marks_the_connection_asking(): void
    {
        $tokens = $this->signInThroughOAuth(User::factory()->create());

        $this->withToken($tokens['access_token'])->json('GET', '/api/v1/oauth-connections')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.is_current', true)
            ->assertJsonPath('data.0.attributes.label', 'Pixel 9');
    }

    #[Test]
    public function it_shows_a_connection(): void
    {
        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/oauth-connections/{$connection->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => $this->jsonStructure]);
    }

    #[Test]
    public function it_returns_not_found_for_a_connection_of_another_account(): void
    {
        $connection = OAuthConnection::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/oauth-connections/{$connection->id}")->assertNotFound();
        $this->json('DELETE', "/api/v1/oauth-connections/{$connection->id}")->assertNotFound();
    }

    #[Test]
    public function it_ends_a_connection(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/oauth-connections/{$connection->id}")->assertNoContent();

        $this->assertNotNull($connection->refresh()->ended_at);
        $this->json('DELETE', "/api/v1/oauth-connections/{$connection->id}")->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_connection_already_ended(): void
    {
        $user = User::factory()->create();
        $connection = OAuthConnection::factory()->ended()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/oauth-connections/{$connection->id}")->assertNotFound();
    }
}
