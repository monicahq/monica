<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateGroupMember;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupMemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_somebody_left(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Temp']);

        MonicaServer::actingAs($user)->tool(UpdateGroupMember::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'membership' => $ryan->uuid, 'left_on' => '2007-01-01'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertFalse($ryan->fresh()->isCurrent());
        $this->assertSame('Temp', $ryan->fresh()->role);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.members.update', app(UpdateGroupMember::class)->mirrors());
    }
}
