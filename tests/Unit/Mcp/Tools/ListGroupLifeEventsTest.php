<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroupLifeEvents;
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupLifeEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_activities_of_the_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'group_id' => $group->id, 'title' => 'Budget review']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Booze cruise']);

        MonicaServer::actingAs($user)->tool(ListGroupLifeEvents::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertSee('Budget review')
            ->assertDontSee('Booze cruise');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.lifeEvents', app(ListGroupLifeEvents::class)->mirrors());
    }
}
