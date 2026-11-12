<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Knowledge;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListColumnControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => ['title', 'description', 'folder' => ['id', 'name'], 'columns', 'items', 'created_at', 'updated_at'],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_adds_a_column(): void
    {
        Queue::fake();

        [$user, $vault, $list] = $this->clients();
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns", ['name' => 'City'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.columns.1.name', 'City');
    }

    #[Test]
    public function it_renames_a_column_and_moves_it(): void
    {
        Queue::fake();

        [$user, $vault, $list] = $this->clients();
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);
        $town = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Town', 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$town->uuid}", ['name' => 'City', 'position' => 1])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.columns.0.name', 'City');
    }

    #[Test]
    public function an_empty_name_keeps_the_one_the_column_had(): void
    {
        Queue::fake();

        [$user, $vault, $list] = $this->clients();
        $town = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Town']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$town->uuid}", ['name' => ''])
            ->assertOk();

        $this->assertEquals('Town', $town->refresh()->name);
    }

    #[Test]
    public function it_deletes_a_column_and_refuses_the_last_one(): void
    {
        Queue::fake();

        [$user, $vault, $list] = $this->clients();
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);
        $note = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$note->uuid}")->assertNoContent();
        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$name->uuid}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('column');
    }

    #[Test]
    public function a_column_of_another_list_is_not_found(): void
    {
        [$user, $vault, $list] = $this->clients();
        $suppliers = KnowledgeList::factory()->create(['knowledge_folder_id' => $list->knowledge_folder_id]);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $suppliers->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$column->uuid}", ['name' => 'City'])
            ->assertNotFound();
        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/lists/{$list->uuid}/columns/{$column->uuid}")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: KnowledgeList} */
    private function clients(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Clients',
        ]);

        return [$user, $vault, $list];
    }
}
