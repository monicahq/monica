<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetContactRelationship;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetContactRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_relationship(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $relationship->contactB->uuid,
            'relationship' => $relationship->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee($relationship->uuid);
    }

    #[Test]
    public function it_refuses_a_relationship_the_contact_is_not_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(GetContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $dwight->uuid,
            'relationship' => $relationship->uuid,
        ])->assertHasErrors(['That relationship does not exist.']);
    }
}
