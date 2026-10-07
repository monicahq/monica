<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListContactNameRepresentations;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactNameRepresentationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_way_the_name_is_written(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'formatted_name' => 'マイケル・スコット']);

        EricaServer::actingAs($user)->tool(ListContactNameRepresentations::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
        ])->assertOk()->assertSee('マイケル・スコット')->assertSee('Michael');
    }

    #[Test]
    public function it_does_not_find_a_contact_through_another_vault_or_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListContactNameRepresentations::class, ['vault' => $elsewhere->uuid, 'contact' => $contact->uuid])
            ->assertHasErrors(['That contact does not exist.']);

        EricaServer::actingAs(User::factory()->create())->tool(ListContactNameRepresentations::class, ['vault' => $vault->uuid, 'contact' => $contact->uuid])
            ->assertHasErrors(['That vault does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.nameRepresentations', app(ListContactNameRepresentations::class)->mirrors());
    }
}
