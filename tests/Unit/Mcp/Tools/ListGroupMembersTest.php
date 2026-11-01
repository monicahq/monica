<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListGroupMembers;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupMembersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_memberships_of_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Assistant to the regional manager']);

        EricaServer::actingAs($user)->tool(ListGroupMembers::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertSee('Assistant to the regional manager');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.members', app(ListGroupMembers::class)->mirrors());
    }
}
