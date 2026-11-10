<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupFilesBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupFilesBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_files_the_latest_first_with_what_they_are(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $older = GroupFile::factory()->create(['group_id' => $group->id, 'created_at' => '2026-03-03 10:00:00']);
        $older->file->update(['original_name' => 'reading-list.pdf', 'mime_type' => 'application/pdf', 'size_in_bytes' => 184320]);
        $newer = GroupFile::factory()->create(['group_id' => $group->id, 'created_at' => '2026-10-01 10:00:00']);
        $newer->file->update(['original_name' => 'club-photo.jpg', 'mime_type' => 'image/jpeg']);

        $files = new GroupFilesBlock(user: $user, vault: $vault, group: $group)->files();

        $this->assertSame(['club-photo.jpg', 'reading-list.pdf'], array_column($files, 'name'));
        $this->assertSame(['memory', 'file'], array_column($files, 'icon'));
        $this->assertStringStartsWith('PDF · 180', $files[1]['meta']);
        $this->assertSame(route('files.show', ['vault' => $vault, 'file' => $older->file]), $files[1]['url']);
    }

    #[Test]
    public function it_keeps_the_panel_to_the_latest_few(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupFile::factory()->count(GroupFilesBlock::PANEL_COUNT + 1)->create(['group_id' => $group->id]);

        $block = new GroupFilesBlock(user: $user, vault: $vault, group: $group);

        $this->assertCount(GroupFilesBlock::PANEL_COUNT, $block->latest());
        $this->assertSame(GroupFilesBlock::PANEL_COUNT + 1, $block->count());
    }
}
