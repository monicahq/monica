<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateGroupLifeEvent;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateGroupLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_activity_on_the_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $meeting = $this->lifeEventTypeAsking($user, 'Meeting');

        EricaServer::actingAs($user)->tool(CreateGroupLifeEvent::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'life_event_type' => $meeting->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$angela->uuid],
            'title' => 'Budget review',
        ])
            ->assertOk()
            ->assertSee('Budget review');

        $this->assertDatabaseHas('life_events', ['vault_id' => $vault->id, 'group_id' => $group->id, 'title' => 'Budget review', 'created_from' => 'group']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.lifeEvents.create', app(CreateGroupLifeEvent::class)->mirrors());
    }
}
