<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class RecordStatisticsControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'kind' => ['id', 'singular', 'plural'],
                'overview' => ['records', 'templates_in_use', 'on_older_versions', 'archived', 'partial'],
                'templates' => [['id', 'name', 'is_default', 'is_active', 'records', 'share', 'published_version', 'versions']],
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_gives_the_statistics_of_an_entity_type(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $response = $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$fixture->animals->uuid}/statistics");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.overview.records', 18);
        $response->assertJsonPath('data.attributes.templates.0.versions.1', ['number' => 2, 'records' => 3, 'is_published' => false]);
    }

    #[Test]
    public function it_gives_the_statistics_of_people(): void
    {
        $fixture = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/contacts/statistics")
            ->assertOk()
            ->assertJsonPath('data.attributes.kind.id', 'contacts')
            ->assertJsonPath('data.attributes.overview.partial', 0);
    }

    #[Test]
    public function it_returns_not_found_for_a_kind_of_another_account(): void
    {
        $fixture = AnimalsVault::build();
        $stranger = AnimalsVault::build();
        Sanctum::actingAs($fixture->user);

        $this->json('GET', "/api/v1/vaults/{$fixture->vault->uuid}/record-kinds/{$stranger->animals->uuid}/statistics")
            ->assertNotFound();
    }
}
