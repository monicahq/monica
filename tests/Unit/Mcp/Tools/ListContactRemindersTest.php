<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListContactReminders;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_reminders_about_a_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['name' => 'Ask about the art school']);

        EricaServer::actingAs($user)->tool(ListContactReminders::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee('Ask about the art school');
    }
}
