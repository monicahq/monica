<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateGroup;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_group_and_keeps_what_is_left_out(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch', 'description' => 'Karen and Andy']);

        MonicaServer::actingAs($user)->tool(UpdateGroup::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'name' => 'Scranton branch'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'Scranton branch', 'description' => 'Karen and Andy']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.update', app(UpdateGroup::class)->mirrors());
    }
}
