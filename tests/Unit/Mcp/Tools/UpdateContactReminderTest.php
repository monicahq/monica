<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateContactReminder;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_a_reminder(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['frequency_interval' => 3]);

        EricaServer::actingAs($user)->tool(UpdateContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'reminder' => $reminder->uuid,
            'name' => 'Congratulate her on the gallery',
            'cadence' => 'yearly',
            'time' => '10:00',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'name' => 'Congratulate her on the gallery', 'cadence' => 'yearly', 'frequency_interval' => 3]);
    }

    #[Test]
    public function it_refuses_a_first_date_on_a_reminder_reading_a_date_on_the_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $value = FieldValue::factory()->recurringDate()->create(['valuable_id' => $contact->id]);
        $reminder = Reminder::factory()->create(['field_value_id' => $value->id]);

        EricaServer::actingAs($user)->tool(UpdateContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'reminder' => $reminder->uuid,
            'name' => 'Pam’s birthday',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'yearly',
            'time' => '09:00',
        ])->assertHasErrors(['This reminder reads its date from the contact.']);
    }
}
