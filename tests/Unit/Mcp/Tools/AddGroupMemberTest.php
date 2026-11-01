<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\AddGroupMember;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddGroupMemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_contact_to_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        EricaServer::actingAs($user)->tool(AddGroupMember::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'contact' => $pam->uuid, 'role' => 'Receptionist'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'contact_id' => $pam->id, 'role' => 'Receptionist']);
    }

    #[Test]
    public function it_refuses_somebody_already_in_the_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $membership = GroupMember::factory()->create(['group_id' => $group->id]);

        EricaServer::actingAs($user)->tool(AddGroupMember::class, ['vault' => $vault->uuid, 'group' => $group->uuid, 'contact' => $membership->contact->uuid])
            ->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.members.create', app(AddGroupMember::class)->mirrors());
    }
}
