<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
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

        MonicaServer::actingAs($user)->tool(UpdateContactNameRepresentations::class, [
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

        MonicaServer::actingAs($user)->tool(UpdateContactNameRepresentations::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'name_representations' => [['kind' => 'phonetic', 'given_name' => 'Maikeru']],
            'display' => 0,
        ])->assertHasErrors();
    }
}
