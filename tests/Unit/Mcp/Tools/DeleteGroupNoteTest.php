<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteGroupNote;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_note(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        MonicaServer::actingAs($user)->tool(DeleteGroupNote::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'note' => $note->uuid])
            ->assertOk();

        $this->assertSoftDeleted($note);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.notes.destroy', app(DeleteGroupNote::class)->mirrors());
    }
}
