<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListContactRelationships;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_relationships_of_a_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam']);
        Relationship::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'contact_a_id' => $jim->id, 'contact_b_id' => $pam->id]);

        MonicaServer::actingAs($user)->tool(ListContactRelationships::class, [
            'vault' => $vault->uuid,
            'contact' => $pam->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee($jim->uuid);
    }
}
