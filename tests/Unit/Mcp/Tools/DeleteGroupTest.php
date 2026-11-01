<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteGroup;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        EricaServer::actingAs($user)->tool(DeleteGroup::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($group);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.destroy', app(DeleteGroup::class)->mirrors());
    }
}
