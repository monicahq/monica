<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetContactReminder;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetContactReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_reminder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['name' => 'Ask about the art school']);

        EricaServer::actingAs($user)->tool(GetContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'reminder' => $reminder->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee('Ask about the art school');
    }

    #[Test]
    public function it_refuses_a_reminder_about_another_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($jim, now()->addWeek()->toDateString())->create();

        EricaServer::actingAs($user)->tool(GetContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $pam->uuid,
            'reminder' => $reminder->uuid,
        ])->assertHasErrors(['That reminder does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.reminders.show', app(GetContactReminder::class)->mirrors());
    }
}
