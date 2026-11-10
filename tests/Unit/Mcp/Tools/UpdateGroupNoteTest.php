<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateGroupNote;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_a_note_says(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        EricaServer::actingAs($user)->tool(UpdateGroupNote::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'note' => $note->uuid, 'body' => 'Paper plates are fine.'])
            ->assertOk()
            ->assertSee('Paper plates are fine.');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.notes.update', app(UpdateGroupNote::class)->mirrors());
    }
}
