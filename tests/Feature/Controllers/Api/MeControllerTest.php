<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'first_name',
                'last_name',
                'email',
                'email_verified',
                'locale',
                'timezone',
                'time_format',
                'date_format',
                'name_order',
                'number_format',
                'contact_list_grouping',
                'contact_list_sort',
                'contact_list_direction',
                'created_at',
                'updated_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_refuses_the_key_of_a_deactivated_person_until_they_are_reactivated(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dundie awards bot')->plainTextToken;

        $user->update(['is_active' => false]);
        $this->withToken($key)->json('GET', '/api/v1/me')->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $user->update(['is_active' => true]);
        $this->withToken($key)->json('GET', '/api/v1/me')->assertOk();
    }

    #[Test]
    public function it_shows_the_view_the_contact_list_opens_on(): void
    {
        $user = User::factory()->create([
            'contact_list_grouping' => 'favourites',
            'contact_list_sort' => 'last-spoke',
            'contact_list_direction' => 'desc',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.attributes.contact_list_grouping', 'favourites')
            ->assertJsonPath('data.attributes.contact_list_sort', 'last-spoke')
            ->assertJsonPath('data.attributes.contact_list_direction', 'desc');
    }

    #[Test]
    public function it_shows_no_contact_list_view_until_somebody_has_opened_the_list(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.attributes.contact_list_grouping', null)
            ->assertJsonPath('data.attributes.contact_list_sort', null)
            ->assertJsonPath('data.attributes.contact_list_direction', null);
    }

    #[Test]
    public function it_shows_the_user_the_token_belongs_to(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/me');

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'user');
        $response->assertJsonPath('data.id', (string) $user->id);
        $response->assertJsonPath('data.attributes.email', 'michael.scott@dundermifflin.com');
    }

    #[Test]
    public function it_never_hands_out_a_credential(): void
    {
        $user = User::factory()->twoFactor()->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/me');

        $response->assertOk();
        $response->assertJsonMissingPath('data.attributes.password');
        $response->assertJsonMissingPath('data.attributes.two_factor_secret');
        $response->assertJsonMissingPath('data.attributes.two_factor_recovery_codes');
        $response->assertJsonMissingPath('data.attributes.last_login_ip');
    }

    #[Test]
    public function it_updates_the_profile(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', '/api/v1/me', [
            'first_name' => 'Michael',
            'last_name' => 'Scarn',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.last_name', 'Scarn');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'last_name' => 'Scarn',
        ]);
    }

    #[Test]
    public function it_validates_the_fields_when_updating_the_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', '/api/v1/me', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    #[Test]
    public function it_leaves_the_sign_in_address_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->json('PUT', '/api/v1/me', [
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'prison.mike@dundermifflin.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.email', 'michael.scott@dundermifflin.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);
    }

    #[Test]
    public function it_opens_no_browser_session_for_a_request_coming_from_the_interface(): void
    {
        $response = $this->withHeader('Referer', config('app.url'))->json('GET', '/api/v1/me');

        $response->assertUnauthorized();
        $response->assertCookieMissing(config('session.cookie'));
    }
}
