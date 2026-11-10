<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListGroupFiles;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupFilesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_files_kept_with_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        EricaServer::actingAs($user)->tool(ListGroupFiles::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertSee($attachment->file->original_name);
    }

    #[Test]
    public function it_is_withdrawn_when_file_upload_is_not_offered(): void
    {
        $user = User::factory()->create();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        EricaServer::actingAs($user)->tool(ListGroupFiles::class, ['vault' => 'x', 'group' => 'y'])
            ->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.files', app(ListGroupFiles::class)->mirrors());
    }
}
