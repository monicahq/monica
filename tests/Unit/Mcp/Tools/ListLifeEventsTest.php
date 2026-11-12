<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListLifeEvents;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLifeEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_life_events_of_a_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $this->lifeEventWith($jim, 'Booze cruise');

        MonicaServer::actingAs($user)->tool(ListLifeEvents::class, [
            'vault' => $vault->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee('Booze cruise');
    }

    #[Test]
    public function it_lists_the_life_events_one_contact_took_part_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $this->lifeEventWith($jim, 'Booze cruise');
        $this->lifeEventWith($pam, 'Art show');

        MonicaServer::actingAs($user)->tool(ListLifeEvents::class, [
            'vault' => $vault->uuid,
            'contact' => $pam->uuid,
        ])->assertOk()->assertSee('Art show')->assertDontSee('Booze cruise');
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $stamford = Vault::factory()->create();

        MonicaServer::actingAs($user)->tool(ListLifeEvents::class, [
            'vault' => $stamford->uuid,
        ])->assertHasErrors();
    }

    private function lifeEventWith(Contact $contact, string $title): void
    {
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $contact->vault_id, 'title' => $title, 'participant_count' => 1]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id]);
    }
}
