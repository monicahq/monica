<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListContactGroups;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListContactGroupsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_a_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        GroupMember::factory()->create(['contact_id' => $dwight->id, 'group_id' => Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm'])->id]);

        MonicaServer::actingAs($user)->tool(ListContactGroups::class, ['vault' => $vault->uuid, 'contact' => $dwight->uuid])
            ->assertOk()
            ->assertSee('Schrute farm');
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.contacts.groups', app(ListContactGroups::class)->mirrors());
    }
}
