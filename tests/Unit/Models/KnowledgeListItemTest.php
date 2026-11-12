<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_text_it_holds_in_a_column(): void
    {
        $item = KnowledgeListItem::factory()->create();
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $item->knowledge_list_id, 'name' => 'City']);
        KnowledgeListCell::factory()->create(['knowledge_list_item_id' => $item->id, 'knowledge_list_column_id' => $city->id, 'value' => 'Scranton']);

        $this->assertEquals('Scranton', $item->valueFor($city));
    }

    #[Test]
    public function a_blank_cell_gives_nothing(): void
    {
        $item = KnowledgeListItem::factory()->create();
        $note = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $item->knowledge_list_id, 'name' => 'Note']);

        $this->assertNull($item->valueFor($note));
    }
}
