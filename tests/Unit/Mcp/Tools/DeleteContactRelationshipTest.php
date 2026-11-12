<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeleteContactRelationship;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteContactRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_relationship(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeleteContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $relationship->contactA->uuid,
            'relationship' => $relationship->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSoftDeleted($relationship);
        $this->assertModelExists($relationship->contactB);
    }

    #[Test]
    public function it_refuses_a_relationship_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeleteContactRelationship::class, [
            'vault' => $vault->uuid,
            'contact' => $contact->uuid,
            'relationship' => '6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f',
        ])->assertHasErrors();
    }
}
