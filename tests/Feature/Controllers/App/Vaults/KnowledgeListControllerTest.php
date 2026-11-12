<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_list_and_opens_it_ready_for_a_first_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('knowledge.lists.create', $vault));

        $list = KnowledgeList::query()->where('vault_id', $vault->id)->firstOrFail();
        $response->assertRedirect(route('knowledge.lists.show', [$vault, $list]));

        $this->actingAs($user)->get(route('knowledge.lists.show', [$vault, $list]))
            ->assertOk()
            ->assertSee('Untitled list')
            ->assertSee('x-data="{ adding: true }"', escape: false)
            ->assertSee(route('knowledge.listItems.create', [$vault, $list]), escape: false);
    }

    #[Test]
    public function it_opens_the_list_form_and_saves_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);

        $this->actingAs($user)->get(route('knowledge.lists.edit', [$vault, $list]))
            ->assertOk()
            ->assertSee('name="description"', escape: false);

        $this->actingAs($user)->put(route('knowledge.lists.update', [$vault, $list]), [
            'title' => 'Paper clients',
            'description' => 'Who buys from us.',
        ])
            ->assertRedirect(route('knowledge.lists.show', [$vault, $list]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_lists', ['id' => $list->id, 'title' => 'Paper clients', 'description' => 'Who buys from us.']);
    }

    #[Test]
    public function it_deletes_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);

        $this->actingAs($user)->delete(route('knowledge.lists.destroy', [$vault, $list]))
            ->assertRedirect(route('knowledge.index', $vault));

        $this->assertModelMissing($list);
    }

    #[Test]
    public function a_list_reached_through_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list(Vault::factory()->create(['account_id' => $user->account_id]));

        $this->actingAs($user)->get(route('knowledge.lists.show', [$vault, $list]))->assertNotFound();
    }

    #[Test]
    public function a_folder_of_another_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $elsewhere = KnowledgeFolder::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->post(route('knowledge.lists.create', $vault), ['folder' => $elsewhere->uuid])->assertNotFound();
        $this->actingAs($user)->put(route('knowledge.lists.update', [$vault, $list]), ['folder' => $elsewhere->uuid])->assertNotFound();

        $this->assertDatabaseCount('knowledge_lists', 1);
    }

    private function list(Vault $vault): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
        ]);
    }
}
