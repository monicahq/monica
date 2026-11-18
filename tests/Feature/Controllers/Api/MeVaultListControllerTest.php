<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeVaultListControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'shows_vault_list',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_hides_the_list_of_vaults(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.vaultList.update'), [
            'shows_vault_list' => false,
        ]);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.shows_vault_list', false);

        $this->assertFalse($user->refresh()->shows_vault_list);
    }

    #[Test]
    public function it_refuses_a_value_that_is_not_a_boolean(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->json('PUT', route('api.v1.me.vaultList.update'), [
            'shows_vault_list' => 'sometimes',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('shows_vault_list');
    }
}
