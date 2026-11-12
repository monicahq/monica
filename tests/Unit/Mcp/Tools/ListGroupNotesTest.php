<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroupNotes;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupNotesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_notes_of_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'Oscar brings the cheese.']);

        MonicaServer::actingAs($user)->tool(ListGroupNotes::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertSee('Oscar brings the cheese.');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.notes', app(ListGroupNotes::class)->mirrors());
    }
}
