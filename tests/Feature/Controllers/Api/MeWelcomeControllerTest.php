<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeWelcomeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'first_name',
                'welcomed_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_marks_the_user_as_welcomed(): void
    {
        $user = User::factory()->notWelcomed()->create(['first_name' => 'Pam']);
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.welcome.update'));

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.welcomed_at', $user->refresh()->welcomed_at->timestamp);
    }
}
