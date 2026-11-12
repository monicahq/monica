<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateContactReminder;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateContactReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_reminder_about_a_contact(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'name' => 'Ask about the art school',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'monthly',
            'time' => '09:00',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', ['remindable_id' => $contact->id, 'name' => 'Ask about the art school', 'source' => 'manual']);
    }

    #[Test]
    public function it_refuses_a_reminder_that_starts_in_the_past(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'name' => 'Ask about the art school',
            'starts_on' => now()->subWeek()->toDateString(),
            'cadence' => 'once',
            'time' => '09:00',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('reminders', ['remindable_id' => $contact->id]);
    }
}
