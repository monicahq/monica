<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeFolderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->from(route('knowledge.index', $vault))
            ->post(route('knowledge.folders.create', $vault), ['name' => 'Warehouse'])
            ->assertRedirect(route('knowledge.index', $vault))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_folders', ['vault_id' => $vault->id, 'name' => 'Warehouse']);
    }

    #[Test]
    public function it_refuses_a_folder_without_a_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('knowledge.folders.create', $vault), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_renames_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'name' => 'Warehouse']);

        $this->actingAs($user)->put(route('knowledge.folders.update', [$vault, $folder]), ['name' => 'Annex'])
            ->assertSessionHas('status');

        $this->assertEquals('Annex', $folder->refresh()->name);
    }

    #[Test]
    public function it_moves_a_folder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 1]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id, 'position' => 2]);

        $this->actingAs($user)->put(route('knowledge.folderPosition.update', [$vault, $travel]), ['position' => 1])
            ->assertSessionHas('status');

        $this->assertEquals(1, $travel->refresh()->position);
    }

    #[Test]
    public function it_deletes_a_folder_moving_its_notes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $travel = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $note = KnowledgeNote::factory()->create(['knowledge_folder_id' => $home->id]);

        $this->actingAs($user)->delete(route('knowledge.folders.destroy', [$vault, $home]), ['destination' => $travel->uuid])
            ->assertRedirect(route('knowledge.index', $vault))
            ->assertSessionHas('status');

        $this->assertModelMissing($home);
        $this->assertEquals($travel->id, $note->refresh()->knowledge_folder_id);
    }

    #[Test]
    public function a_destination_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $home = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->delete(route('knowledge.folders.destroy', [$vault, $home]), ['destination' => $elsewhere->uuid])
            ->assertNotFound();

        $this->assertModelExists($home);
    }

    #[Test]
    public function deleting_the_last_folder_comes_back_with_the_reason(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->delete(route('knowledge.folders.destroy', [$vault, $folder]), ['destination' => 'delete'])
            ->assertSessionHasErrors(['folder' => 'The last folder cannot be deleted. Knowledge needs at least one.']);

        $this->assertModelExists($folder);
    }
}
