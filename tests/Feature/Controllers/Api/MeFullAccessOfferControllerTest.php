<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeFullAccessOfferControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'first_name',
                'full_access_offer_dismissed_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_hides_the_offer_of_full_access(): void
    {
        $user = User::factory()->create(['first_name' => 'Angela']);
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.fullAccessOffer.update'));

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.full_access_offer_dismissed_at', $user->refresh()->full_access_offer_dismissed_at->timestamp);
    }
}
