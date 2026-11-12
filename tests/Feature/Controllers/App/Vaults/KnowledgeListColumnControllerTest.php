<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListColumnControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_columns_and_says_why_the_last_one_stays(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name']);

        $this->actingAs($user)->get(route('knowledge.listColumns.index', [$vault, $list]))
            ->assertOk()
            ->assertSee('value="Name"', escape: false)
            ->assertSee('aria-disabled="true"', escape: false)
            ->assertSee('The last column cannot be removed. A list needs at least one.');
    }

    #[Test]
    public function it_adds_renames_moves_and_removes_a_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);

        $this->actingAs($user)->post(route('knowledge.listColumns.create', [$vault, $list]), ['name' => 'City'])
            ->assertRedirect(route('knowledge.listColumns.index', [$vault, $list]));

        $city = KnowledgeListColumn::query()->where('name', 'City')->firstOrFail();

        $this->actingAs($user)->put(route('knowledge.listColumns.update', [$vault, $list, $city]), ['name' => 'Town'])
            ->assertSessionHas('status');
        $this->actingAs($user)->put(route('knowledge.listColumnPosition.update', [$vault, $list, $city]), ['position' => 1])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_list_columns', ['id' => $city->id, 'name' => 'Town', 'position' => 1]);

        $this->actingAs($user)->delete(route('knowledge.listColumns.destroy', [$vault, $list, $name]))
            ->assertSessionHas('status');

        $this->assertModelMissing($name);
    }

    #[Test]
    public function removing_the_last_column_comes_back_with_the_reason(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $column = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id]);

        $this->actingAs($user)->delete(route('knowledge.listColumns.destroy', [$vault, $list, $column]))
            ->assertSessionHasErrors(['column' => 'The last column cannot be removed. A list needs at least one.']);
    }

    #[Test]
    public function a_column_of_another_list_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $list = $this->list($vault);
        $elsewhere = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $this->list($vault)->id]);

        $this->actingAs($user)->put(route('knowledge.listColumns.update', [$vault, $list, $elsewhere]), ['name' => 'City'])
            ->assertNotFound();
    }

    private function list(Vault $vault): KnowledgeList
    {
        return KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
        ]);
    }
}
