<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroupReminders;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_reminders_about_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['name' => 'Book the room']);

        MonicaServer::actingAs($user)->tool(ListGroupReminders::class, ['vault' => $vault->uuid, 'group' => $group->uuid])
            ->assertOk()
            ->assertSee('Book the room');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminders', app(ListGroupReminders::class)->mirrors());
    }
}
