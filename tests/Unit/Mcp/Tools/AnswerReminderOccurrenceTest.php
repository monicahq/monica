<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\AnswerReminderOccurrence;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnswerReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_answer(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        EricaServer::actingAs($user)->tool(AnswerReminderOccurrence::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'occurrence' => $occurrence->uuid,
            'answer' => 'forgotten',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'forgotten']);
    }

    #[Test]
    public function it_refuses_a_second_answer(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->remembered()->create(['reminder_id' => $reminder->id]);

        EricaServer::actingAs($user)->tool(AnswerReminderOccurrence::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'occurrence' => $occurrence->uuid,
            'answer' => 'forgotten',
        ])->assertHasErrors(['This reminder has already been answered.']);

        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'remembered']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.reminderOccurrences.answer.update', app(AnswerReminderOccurrence::class)->mirrors());
    }
}
