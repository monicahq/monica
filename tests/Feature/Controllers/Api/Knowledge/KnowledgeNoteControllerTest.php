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

class KnowledgeNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => ['title', 'body', 'headings', 'folder' => ['id', 'name'], 'created_at', 'updated_at'],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_titles_only_and_leaves_out_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id, 'title' => 'Dundies', 'body' => 'Long text']);
        KnowledgeNote::factory()->create(['title' => 'Elsewhere']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/notes")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.title', 'Dundies')
            ->assertJsonMissingPath('data.0.attributes.body');
    }

    #[Test]
    public function it_writes_reads_changes_and_deletes_a_note(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $id = $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/notes", ['title' => 'Dundies', 'body' => "## Awards\nBest dressed"])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.folder.name', 'General')
            ->assertJsonPath('data.attributes.headings.0.text', 'Awards')
            ->json('data.id');

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/notes/{$id}", ['body' => null])
            ->assertOk()
            ->assertJsonPath('data.attributes.title', 'Dundies')
            ->assertJsonPath('data.attributes.body', null);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/notes/{$id}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/knowledge/notes/{$id}")
            ->assertNoContent();
    }

    #[Test]
    public function it_refuses_a_title_too_long(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/notes", ['title' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    #[Test]
    public function a_folder_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/notes?folder={$elsewhere->uuid}")->assertNotFound();
        $this->json('POST', "/api/v1/vaults/{$vault->slug}/knowledge/notes", ['folder_id' => $elsewhere->uuid])->assertNotFound();
        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/knowledge/notes/{$note->uuid}", ['folder_id' => $elsewhere->uuid])->assertNotFound();

        $this->assertDatabaseCount('knowledge_notes', 1);
    }

    #[Test]
    public function a_note_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/knowledge/notes/{$note->uuid}")->assertNotFound();
    }
}
