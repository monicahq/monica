<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_of_a_contact_current_first_and_leaves_deleted_groups_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $left = GroupMember::factory()->former()->create(['contact_id' => $dwight->id, 'group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);
        $farm = GroupMember::factory()->create(['contact_id' => $dwight->id, 'group_id' => Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Schrute farm'])->id]);
        $deleted = Group::factory()->create(['vault_id' => $vault->id]);
        GroupMember::factory()->create(['contact_id' => $dwight->id, 'group_id' => $deleted->id]);
        $deleted->delete();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$dwight->uuid}/groups");

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $farm->uuid);
        $response->assertJsonPath('data.1.id', $left->uuid);
    }
}
