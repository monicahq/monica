<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteGroupReminder;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteGroupReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_reminder_about_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

        EricaServer::actingAs($user)->tool(DeleteGroupReminder::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'reminder' => $reminder->uuid])
            ->assertOk();

        $this->assertSoftDeleted($reminder);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminders.destroy', app(DeleteGroupReminder::class)->mirrors());
    }
}
