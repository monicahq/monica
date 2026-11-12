<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeListTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_its_columns_in_their_order(): void
    {
        $list = KnowledgeList::factory()->create();
        $city = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'City', 'position' => 2]);
        $name = KnowledgeListColumn::factory()->create(['knowledge_list_id' => $list->id, 'name' => 'Name', 'position' => 1]);

        $this->assertEquals([$name->id, $city->id], $list->columns()->pluck('id')->all());
    }

    #[Test]
    public function it_reads_its_items_in_their_order(): void
    {
        $list = KnowledgeList::factory()->create();
        $second = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id, 'position' => 2]);
        $first = KnowledgeListItem::factory()->create(['knowledge_list_id' => $list->id, 'position' => 1]);

        $this->assertEquals([$first->id, $second->id], $list->items()->pluck('id')->all());
    }
}
