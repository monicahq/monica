<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateContactNameRepresentations;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactNameRepresentationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replaces_every_way_the_name_is_written_and_keeps_the_nickname_it_was_not_given(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott', 'nickname' => 'Prison Mike']);

        EricaServer::actingAs($user)->tool(UpdateContactNameRepresentations::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'name_representations' => [
                ['id' => $contact->displayNameRepresentation->uuid, 'kind' => 'written', 'given_name' => 'Michael', 'family_name' => 'Scott'],
                ['kind' => 'transliteration', 'script' => 'Kana', 'formatted_name' => 'マイケル・スコット'],
            ],
            'display' => 1,
        ])->assertOk()->assertHasNoErrors();

        $contact->refresh();
        $this->assertSame('マイケル・スコット', $contact->name);
        $this->assertSame('Prison Mike', $contact->nickname);
        $this->assertSame(2, $contact->nameRepresentations()->count());
    }

    #[Test]
    public function it_refuses_a_pronunciation_with_no_system(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(UpdateContactNameRepresentations::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'name_representations' => [['kind' => 'phonetic', 'given_name' => 'Maikeru']],
            'display' => 0,
        ])->assertHasErrors();
    }

    #[Test]
    public function it_does_not_find_a_contact_through_another_vault_or_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Michael']);
        $payload = ['contact' => $contact->uuid, 'name_representations' => [['kind' => 'written', 'given_name' => 'Toby']], 'display' => 0];

        EricaServer::actingAs($user)->tool(UpdateContactNameRepresentations::class, ['vault' => $elsewhere->uuid, ...$payload])
            ->assertHasErrors(['That contact does not exist.']);

        EricaServer::actingAs(User::factory()->create())->tool(UpdateContactNameRepresentations::class, ['vault' => $vault->uuid, ...$payload])
            ->assertHasErrors(['That vault does not exist.']);

        $this->assertSame('Michael', $contact->refresh()->first_name);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.nameRepresentations.update', app(UpdateContactNameRepresentations::class)->mirrors());
    }
}
