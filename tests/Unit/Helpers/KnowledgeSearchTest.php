<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\KnowledgeSearch;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\KnowledgeNote;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_a_note_by_its_title_or_its_text(): void
    {
        $vault = Vault::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        $speech = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Dundies speech', 'body' => null]);
        $safety = KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Warehouse', 'body' => 'Never use the baler alone.']);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Party', 'body' => 'Cake']);

        $this->assertEquals([$speech->id], KnowledgeSearch::in($vault, 'dundies')['notes']->pluck('id')->all());
        $this->assertEquals([$safety->id], KnowledgeSearch::in($vault, 'baler')['notes']->pluck('id')->all());
    }

    #[Test]
    public function every_word_typed_has_to_be_found(): void
    {
        $vault = Vault::factory()->create();
        $folder = KnowledgeFolder::factory()->create(['vault_id' => $vault->id]);
        KnowledgeNote::factory()->create(['knowledge_folder_id' => $folder->id, 'title' => 'Warehouse', 'body' => 'Never use the baler alone.']);

        $this->assertCount(1, KnowledgeSearch::in($vault, 'baler alone')['notes']);
        $this->assertCount(0, KnowledgeSearch::in($vault, 'baler forklift')['notes']);
    }

    #[Test]
    public function it_finds_a_list_by_its_description_or_a_cell_ignoring_accents_and_a_typo(): void
    {
        $vault = Vault::factory()->create();
        $list = KnowledgeList::factory()->create([
            'knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id,
            'title' => 'Restaurants',
            'description' => 'Places for the café crowd.',
        ]);
        $item = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id]);
        KnowledgeListCell::factory()->create([
            'knowledge_list_item_id' => $item->id,
            'knowledge_list_column_id' => KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id])->id,
            'value' => 'Scranton',
        ]);

        $this->assertEquals([$list->id], KnowledgeSearch::in($vault, 'scranon')['lists']->pluck('id')->all());
        $this->assertEquals([$list->id], KnowledgeSearch::in($vault, 'cafe')['lists']->pluck('id')->all());
    }

    #[Test]
    public function it_leaves_out_another_vault(): void
    {
        $vault = Vault::factory()->create();
        KnowledgeNote::factory()->create(['title' => 'Dundies speech']);

        $this->assertCount(0, KnowledgeSearch::in($vault, 'dundies')['notes']);
    }

    #[Test]
    public function nothing_typed_finds_nothing(): void
    {
        $vault = Vault::factory()->create();
        KnowledgeNote::factory()->create(['knowledge_folder_id' => KnowledgeFolder::factory()->create(['vault_id' => $vault->id])->id]);

        $this->assertCount(0, KnowledgeSearch::in($vault, ' !? ')['notes']);
    }
}
