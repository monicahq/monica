<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListGroups;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
        Group::factory()->create();

        EricaServer::actingAs($user)->tool(ListGroups::class, ['vault' => $vault->uuid])
            ->assertOk()
            ->assertSee('Halpert household');
    }

    #[Test]
    public function it_is_not_offered_while_groups_are_off(): void
    {
        $user = User::factory()->create();
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        EricaServer::actingAs($user)->tools()->assertNotRegistered([ListGroups::class]);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups', app(ListGroups::class)->mirrors());
    }
}
