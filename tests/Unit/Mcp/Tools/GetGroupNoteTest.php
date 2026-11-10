<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetGroupNote;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_note(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'No paper plates.']);

        EricaServer::actingAs($user)->tool(GetGroupNote::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'note' => $note->uuid])
            ->assertOk()
            ->assertSee('No paper plates.');
    }

    #[Test]
    public function it_does_not_find_a_note_of_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        EricaServer::actingAs($user)->tool(GetGroupNote::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'note' => $note->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.notes.show', app(GetGroupNote::class)->mirrors());
    }
}
