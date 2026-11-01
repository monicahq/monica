<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\RemoveGroupMember;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveGroupMemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_membership_recorded_by_mistake(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $membership = GroupMember::factory()->create(['group_id' => $group->id]);

        EricaServer::actingAs($user)->tool(RemoveGroupMember::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'membership' => $membership->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($membership);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.members.destroy', app(RemoveGroupMember::class)->mirrors());
    }
}
