<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeTimezoneControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'timezone',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_sets_the_time_zone(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.timezone.update'), [
            'timezone' => 'Europe/Lisbon',
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.timezone', 'Europe/Lisbon');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Lisbon',
        ]);
    }

    #[Test]
    public function it_refuses_a_zone_the_product_does_not_know(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.timezone.update'), [
            'timezone' => 'Nowhere/Here',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('timezone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Paris',
        ]);
    }
}
