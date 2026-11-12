<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateGroupReminder;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateGroupReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_reminder_about_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);

        MonicaServer::actingAs($user)->tool(CreateGroupReminder::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'name' => 'Book the room',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'monthly',
            'time' => '09:00',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', [
            'remindable_type' => $group->getMorphClass(),
            'remindable_id' => $group->id,
            'name' => 'Book the room',
            'source' => 'manual',
        ]);
    }

    #[Test]
    public function it_refuses_a_reminder_that_starts_in_the_past(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);

        MonicaServer::actingAs($user)->tool(CreateGroupReminder::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'name' => 'Book the room',
            'starts_on' => now()->subWeek()->toDateString(),
            'cadence' => 'once',
            'time' => '09:00',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('reminders', ['remindable_id' => $group->id, 'name' => 'Book the room']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminders.create', app(CreateGroupReminder::class)->mirrors());
    }
}
