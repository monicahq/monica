<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Knowledge;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeFolderControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => ['name', 'position', 'note_count', 'list_count', 'notes', 'lists', 'created_at', 'updated_at'],
            'links' => ['web'],
        ],
    ];

    #[Test]
    public function it_lists_the_folders_of_the_vault_and_leaves_out_the_others(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Office']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $office->id, 'title' => 'Dundies']);
        KnowledgeFolder::factory()->create(['name' => 'Elsewhere']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/folders")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.name', 'Office')
            ->assertJsonPath('data.0.attributes.notes.0.title', 'Dundies');
    }

    #[Test]
    public function it_adds_renames_and_moves_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);

        Sanctum::actingAs($user);

        $id = $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/folders", ['name' => 'Warehouse'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.position', 2)
            ->json('data.id');

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$id}", ['name' => 'Annex', 'position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Annex')
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_deletes_a_folder_moving_its_notes_and_refuses_the_last_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $home->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$home->uuid}", ['move_to' => $travel->uuid])
            ->assertNoContent();

        $this->assertEquals($travel->id, $note->refresh()->knowledge_folder_id);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$travel->uuid}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('folder');
    }

    #[Test]
    public function it_refuses_a_folder_without_a_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/folders", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function a_folder_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$folder->uuid}", ['name' => 'Annex'])
            ->assertNotFound();
    }

    #[Test]
    public function a_destination_of_another_vault_is_not_found_and_the_folder_itself_is_refused(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$home->uuid}", ['move_to' => $elsewhere->uuid])
            ->assertNotFound();
        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/folders/{$home->uuid}", ['move_to' => $home->uuid])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('move_to');

        $this->assertModelExists($home);
    }
}
