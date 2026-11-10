<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateGroupReminder;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_a_reminder_about_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['frequency_interval' => 3]);

        EricaServer::actingAs($user)->tool(UpdateGroupReminder::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'reminder' => $reminder->uuid,
            'name' => 'Order the brie',
            'cadence' => 'yearly',
            'time' => '10:00',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'name' => 'Order the brie', 'cadence' => 'yearly', 'frequency_interval' => 3]);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminders.update', app(UpdateGroupReminder::class)->mirrors());
    }
}
