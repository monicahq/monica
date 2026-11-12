<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteContactReminder;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteContactReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_reminder(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        EricaServer::actingAs($user)->tool(DeleteContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'reminder' => $reminder->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSoftDeleted($reminder);
    }

    #[Test]
    public function it_refuses_a_reminder_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteContactReminder::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'reminder' => '6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f',
        ])->assertHasErrors(['That reminder does not exist.']);
    }
}
