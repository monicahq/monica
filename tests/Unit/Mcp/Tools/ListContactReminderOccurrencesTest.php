<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListContactReminderOccurrences;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_answered_dates(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();
        $answered = ReminderOccurrence::factory()->past()->forgotten()->create(['reminder_id' => $reminder->id]);

        EricaServer::actingAs($user)->tool(ListContactReminderOccurrences::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'category' => 'history',
        ])->assertOk()->assertHasNoErrors()->assertSee($answered->uuid);
    }

    #[Test]
    public function it_refuses_a_category_it_does_not_know(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContactReminderOccurrences::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'category' => 'dismissed',
        ])->assertHasErrors();
    }
}
