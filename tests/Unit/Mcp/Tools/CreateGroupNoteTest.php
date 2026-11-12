<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateGroupNote;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateGroupNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_note_on_the_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(CreateGroupNote::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'body' => 'Oscar brings the cheese.'])
            ->assertOk()
            ->assertSee('Oscar brings the cheese.');

        $this->assertDatabaseHas('group_notes', ['group_id' => $group->id, 'body' => 'Oscar brings the cheese.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.notes.create', app(CreateGroupNote::class)->mirrors());
    }
}
