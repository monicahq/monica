<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListGroupReminderOccurrences;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListGroupReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_answered_dates_of_a_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['timezone' => 'UTC']);
        $answered = ReminderOccurrence::factory()->daysFromNow(-40)->forgotten()->create(['reminder_id' => $reminder->id]);

        MonicaServer::actingAs($user)->tool(ListGroupReminderOccurrences::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'category' => 'history'])
            ->assertOk()
            ->assertSee([$answered->uuid, 'forgotten']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminderOccurrences', app(ListGroupReminderOccurrences::class)->mirrors());
    }
}
