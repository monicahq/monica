<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\PreviewListEntry;
use App\ViewModels\Admin\PreviewListViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewListViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_waiting_entries_first_then_admitted_then_registered_oldest_first(): void
    {
        PreviewListEntry::factory()->registered()->create(['email' => 'registered@dundermifflin.com', 'created_at' => now()->subDays(9)]);
        PreviewListEntry::factory()->admitted('a-token')->create(['email' => 'admitted@dundermifflin.com', 'created_at' => now()->subDays(8)]);
        PreviewListEntry::factory()->create(['email' => 'newer.waiting@dundermifflin.com', 'created_at' => now()->subDay()]);
        PreviewListEntry::factory()->create(['email' => 'older.waiting@dundermifflin.com', 'created_at' => now()->subDays(2)]);

        $rows = new PreviewListViewModel()->rows();

        $this->assertEquals([
            'older.waiting@dundermifflin.com',
            'newer.waiting@dundermifflin.com',
            'admitted@dundermifflin.com',
            'registered@dundermifflin.com',
        ], array_column($rows, 'email'));
        $this->assertEquals(['waiting', 'waiting', 'admitted', 'registered'], array_column($rows, 'state'));
    }

    #[Test]
    public function it_offers_to_admit_again_and_nothing_once_registered(): void
    {
        PreviewListEntry::factory()->create(['created_at' => now()->subDays(3)]);
        PreviewListEntry::factory()->admitted('a-token')->create(['created_at' => now()->subDays(2)]);
        PreviewListEntry::factory()->registered()->create(['created_at' => now()->subDay()]);

        $rows = new PreviewListViewModel()->rows();

        $this->assertEquals(['Admit', 'Admit again', null], array_column($rows, 'admitLabel'));
        $this->assertNull($rows[0]['admitted']);
        $this->assertNotNull($rows[1]['admitted']);
    }

    #[Test]
    public function it_counts_only_the_entries_still_waiting(): void
    {
        PreviewListEntry::factory()->count(2)->create();
        PreviewListEntry::factory()->admitted('a-token')->create();

        $this->assertEquals(2, new PreviewListViewModel()->waitingCount());
    }

    #[Test]
    public function it_holds_no_row_when_nobody_is_on_the_list(): void
    {
        $this->assertSame([], new PreviewListViewModel()->rows());
    }
}
