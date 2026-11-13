<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateContactRelationship;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateContactRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_links_two_contacts(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $jim->uuid,
            'relationship_type' => $type->uuid,
            'other_contact' => $pam->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('relationships', ['relationship_type_id' => $type->id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);
    }

    #[Test]
    public function it_keeps_a_person_named_in_the_call_without_a_record(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $michael->uuid,
            'relationship_type' => $type->uuid,
            'first_name' => 'Holly',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'Holly',
            'is_partial' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_relationship_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $jim->uuid,
            'relationship_type' => $type->uuid,
        ])->assertHasErrors();
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $vault;
    }
}
