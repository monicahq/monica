<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\TemplateChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class TemplateChangeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'status',
                'record_kind',
                'from',
                'to',
                'to_version',
                'counts' => ['total', 'done', 'moved', 'skipped', 'failed'],
                'not_moved',
                'created_at',
                'ended_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_starts_a_change_of_many_records(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $response = $this->json('POST', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-changes", [
            'from' => $fixture->horse->uuid,
            'to' => $fixture->pet->uuid,
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.status', 'running');
        $response->assertJsonPath('data.attributes.counts.total', 4);
    }

    #[Test]
    public function it_refuses_a_batch_with_nothing_to_move(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('POST', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/template-changes", [
            'from' => $fixture->bird->uuid,
            'to' => $fixture->pet->uuid,
        ])->assertUnprocessable()->assertJsonPath('errors.to.0', 'No record is left to move.');
    }

    #[Test]
    public function it_shows_a_change(): void
    {
        $fixture = AnimalsVault::build();
        $change = TemplateChange::factory()->create(['vault_id' => $fixture->vault->id]);
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/template-changes/{$change->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.id', $change->uuid);
    }

    #[Test]
    public function it_returns_not_found_for_a_change_of_another_account(): void
    {
        $fixture = AnimalsVault::build();
        $stranger = AnimalsVault::build();
        $change = TemplateChange::factory()->create(['vault_id' => $stranger->vault->id]);
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/template-changes/{$change->uuid}")
            ->assertNotFound();
    }
}
