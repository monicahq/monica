<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'client',
                'last_used_at',
                'expires_at',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_api_keys_of_the_caller_newest_first(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dundie awards bot');
        $user->createToken('Deployment script');

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/api-keys');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.type', 'api_key');
        $response->assertJsonPath('data.0.attributes.name', 'Deployment script');
        $response->assertJsonPath('data.1.attributes.name', 'Dundie awards bot');
        $response->assertJsonPath('data.0.attributes.last_used_at', null);
    }

    #[Test]
    public function it_does_not_list_the_api_keys_of_another_account(): void
    {
        $user = User::factory()->create();

        $stranger = User::factory()->create();
        $stranger->createToken('Scranton branch bot');

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/api-keys');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_shows_an_api_key(): void
    {
        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/api-keys/{$apiKey->id}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'api_key');
        $response->assertJsonPath('data.id', (string) $apiKey->id);
        $response->assertJsonPath('data.attributes.name', 'Dundie awards bot');
        $response->assertJsonPath('data.attributes.created_at', $apiKey->created_at->timestamp);
    }

    #[Test]
    public function it_returns_not_found_for_an_api_key_of_another_account(): void
    {
        $user = User::factory()->create();

        $stranger = User::factory()->create();
        $apiKey = $stranger->createToken('Scranton branch bot')->accessToken;

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/api-keys/{$apiKey->id}")->assertNotFound();
    }

    #[Test]
    public function it_creates_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $response = $this->json('POST', '/api/v1/api-keys', ['name' => 'Dundie awards bot']);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure + ['token']);
        $response->assertJsonPath('data.type', 'api_key');
        $response->assertJsonPath('data.attributes.name', 'Dundie awards bot');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_validates_the_name_when_creating_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $this->json('POST', '/api/v1/api-keys', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->json('POST', '/api/v1/api-keys', ['name' => 'ab'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_deletes_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/api-keys/{$apiKey->id}")->assertNoContent();

        $this->assertModelMissing($apiKey);
    }

    #[Test]
    public function it_returns_not_found_when_deleting_an_api_key_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $stranger = User::factory()->create();
        $apiKey = $stranger->createToken('Scranton branch bot')->accessToken;

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/api-keys/{$apiKey->id}")->assertNotFound();

        $this->assertModelExists($apiKey);
    }

    #[Test]
    public function it_never_hands_the_key_itself_back_after_the_creation(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $created = $this->json('POST', '/api/v1/api-keys', ['name' => 'Dundie awards bot']);
        $token = $created->json('token');
        $apiKey = $user->tokens()->first();

        $this->json('GET', '/api/v1/api-keys')
            ->assertOk()
            ->assertDontSee($token)
            ->assertDontSee($apiKey->token);

        $this->json('GET', "/api/v1/api-keys/{$apiKey->id}")
            ->assertOk()
            ->assertDontSee($token)
            ->assertDontSee($apiKey->token);
    }

    #[Test]
    public function it_lets_a_caller_revoke_the_key_they_are_calling_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot');

        $this->withHeader('Authorization', "Bearer {$apiKey->plainTextToken}")
            ->json('DELETE', "/api/v1/api-keys/{$apiKey->accessToken->id}")
            ->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$apiKey->plainTextToken}")
            ->json('GET', '/api/v1/api-keys')
            ->assertUnauthorized();
    }

    #[Test]
    public function it_creates_a_key_that_reaches_sensitive_fields_when_that_was_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*', 'sensitive-fields']]));

        $response = $this->json('POST', '/api/v1/api-keys', [
            'name' => 'Dwight’s phone',
            'sensitive_fields' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.sensitive_fields', true);
        $this->assertContains('sensitive-fields', $user->tokens()->first()->abilities);
    }

    #[Test]
    public function it_creates_a_key_that_reaches_no_sensitive_field_by_default(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $response = $this->json('POST', '/api/v1/api-keys', ['name' => 'Deployment script']);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.sensitive_fields', false);
        $this->assertNotContains('sensitive-fields', $user->tokens()->first()->abilities);
    }

    #[Test]
    public function it_mints_a_key_for_an_assistant(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $response = $this->json('POST', '/api/v1/api-keys', [
            'name' => 'Dundie awards bot',
            'client' => 'assistant',
        ]);

        $response->assertCreated()->assertJsonPath('data.attributes.client', 'assistant');
    }

    #[Test]
    public function it_treats_a_key_minted_without_a_kind_as_a_personal_script(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $this->json('POST', '/api/v1/api-keys', ['name' => 'Deployment script'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.client', 'script');
    }

    #[Test]
    public function it_refuses_a_kind_it_does_not_know(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $this->json('POST', '/api/v1/api-keys', ['name' => 'Dundie awards bot', 'client' => 'toaster'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client']);
    }

    #[Test]
    public function it_creates_a_key_that_administers_the_instance_for_an_administrator(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $presentedKey = $user->createToken('Support desk', ['*', 'administration'])->plainTextToken;

        $response = $this->withToken($presentedKey)->json('POST', '/api/v1/api-keys', [
            'name' => 'Support queue bot',
            'administration' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.administration', true);
        $this->assertContains('administration', $user->tokens()->where('name', 'Support queue bot')->first()->abilities);
    }

    #[Test]
    public function it_refuses_the_administration_scope_to_somebody_who_does_not_administer_the_instance(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $user->withAccessToken($user->tokens()->make(['abilities' => ['*']]));

        $response = $this->json('POST', '/api/v1/api-keys', [
            'name' => 'Support queue bot',
            'administration' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('administration');
        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_refuses_the_sensitive_fields_scope_to_a_key_that_does_not_hold_it(): void
    {
        $user = User::factory()->create();
        $presentedKey = $user->createToken('Dwight’s phone', ['*'])->plainTextToken;

        $response = $this->withToken($presentedKey)->json('POST', '/api/v1/api-keys', [
            'name' => 'Schrute Farms export',
            'sensitive_fields' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('sensitive_fields');
        $this->assertSame(1, $user->tokens()->count());
    }
}
