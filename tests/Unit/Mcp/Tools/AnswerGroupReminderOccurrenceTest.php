<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\AnswerGroupReminderOccurrence;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnswerGroupReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_answer(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        MonicaServer::actingAs($user)->tool(AnswerGroupReminderOccurrence::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'occurrence' => $occurrence->uuid,
            'answer' => 'forgotten',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'forgotten']);
    }

    #[Test]
    public function it_does_not_find_a_date_of_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        MonicaServer::actingAs($user)->tool(AnswerGroupReminderOccurrence::class, [
            'vault' => $vault->uuid,
            'group' => $group->uuid,
            'occurrence' => $occurrence->uuid,
            'answer' => 'remembered',
        ])->assertHasErrors(['That reminder date does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.reminderOccurrences.answer.update', app(AnswerGroupReminderOccurrence::class)->mirrors());
    }
}
