<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetGroupReminder;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetGroupReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_reminder_about_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['name' => 'Book the room']);

        EricaServer::actingAs($user)->tool(GetGroupReminder::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'reminder' => $reminder->uuid])
            ->assertOk()
            ->assertSee(['Book the room', 'Finer things club']);
    }

    #[Test]
    public function it_does_not_find_a_reminder_about_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), now()->addWeek()->toDateString())->create();

        EricaServer::actingAs($user)->tool(GetGroupReminder::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'reminder' => $reminder->uuid])
            ->assertHasErrors(['That reminder does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminders.show', app(GetGroupReminder::class)->mirrors());
    }
}
