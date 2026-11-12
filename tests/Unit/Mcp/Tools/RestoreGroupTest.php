<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\RestoreGroup;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_a_deleted_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->delete();

        MonicaServer::actingAs($user)->tool(RestoreGroup::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertNotSoftDeleted($group);
    }

    #[Test]
    public function it_refuses_a_group_that_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(RestoreGroup::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertHasErrors(['That group is not deleted.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.restoration.update', app(RestoreGroup::class)->mirrors());
    }
}
