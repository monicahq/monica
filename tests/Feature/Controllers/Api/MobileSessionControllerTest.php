<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\MobileSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['device_name', 'is_current', 'last_used_at', 'created_at', 'updated_at'],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_phones_signed_in_newest_first(): void
    {
        $user = User::factory()->create();
        $older = MobileSession::factory()->create(['user_id' => $user->id, 'device_name' => 'Pixel 9']);
        $newer = MobileSession::factory()->create(['user_id' => $user->id, 'device_name' => 'Galaxy S26']);
        MobileSession::factory()->ended()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/mobile-sessions');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta']);
        $response->assertJsonPath('data.0.id', (string) $newer->id);
        $response->assertJsonPath('data.1.id', (string) $older->id);
        $response->assertJsonPath('data.0.attributes.is_current', false);
    }

    #[Test]
    public function it_does_not_list_the_phones_of_another_account(): void
    {
        MobileSession::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/mobile-sessions')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_marks_the_phone_asking(): void
    {
        $tokens = $this->signInFromThePhone(User::factory()->create());

        $this->withToken($tokens['access_token'])->json('GET', '/api/v1/mobile-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.is_current', true)
            ->assertJsonPath('data.0.attributes.device_name', 'Pixel 9');
    }

    #[Test]
    public function it_shows_a_phone(): void
    {
        $user = User::factory()->create();
        $session = MobileSession::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/mobile-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => $this->jsonStructure]);
    }

    #[Test]
    public function it_returns_not_found_for_a_phone_of_another_account(): void
    {
        $session = MobileSession::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/mobile-sessions/{$session->id}")->assertNotFound();
        $this->json('DELETE', "/api/v1/mobile-sessions/{$session->id}")->assertNotFound();
    }

    #[Test]
    public function it_signs_a_phone_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $session = MobileSession::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/mobile-sessions/{$session->id}")->assertNoContent();

        $this->assertNotNull($session->refresh()->ended_at);
    }

    #[Test]
    public function it_returns_not_found_for_a_phone_already_signed_out(): void
    {
        $user = User::factory()->create();
        $session = MobileSession::factory()->ended()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/mobile-sessions/{$session->id}")->assertNotFound();
    }
}
