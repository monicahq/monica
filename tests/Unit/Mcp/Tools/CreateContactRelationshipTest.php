<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateContactRelationship;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
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

        EricaServer::actingAs($user)->tool(CreateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $jim->uuid,
            'relationship_type' => $type->uuid,
            'other_contact' => $pam->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('relationships', ['relationship_type_id' => $type->id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);
    }

    #[Test]
    public function it_refuses_a_relationship_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create();

        EricaServer::actingAs($user)->tool(CreateContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $jim->uuid,
            'relationship_type' => $type->uuid,
        ])->assertHasErrors(['That relationship type does not exist.']);
    }
}
