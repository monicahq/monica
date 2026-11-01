<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'group' => ['id', 'name'],
            'contact' => ['id', 'slug', 'name'],
            'role',
            'joined_on',
            'joined_precision',
            'left_on',
            'left_precision',
            'is_current',
        ],
    ];

    #[Test]
    public function it_lists_current_members_before_former_ones(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();
        $ryan = GroupMember::factory()->former()->create(['group_id' => $group->id]);
        $jim = GroupMember::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonPath('data.0.id', $jim->uuid);
        $response->assertJsonPath('data.1.id', $ryan->uuid);
    }

    #[Test]
    public function it_adds_a_contact_of_the_vault(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members", [
            'contact' => $pam->uuid,
            'role' => 'Receptionist',
            'joined_on' => '2001-01-01',
            'joined_precision' => 'year',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.role', 'Receptionist');
        $response->assertJsonPath('data.attributes.is_current', true);
    }

    #[Test]
    public function it_refuses_somebody_already_in_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $dwight = GroupMember::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members", ['contact' => $dwight->contact->uuid])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contact');
    }

    #[Test]
    public function it_does_not_find_a_contact_of_another_vault(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();
        $karen = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members", ['contact' => $karen->uuid])->assertNotFound();
    }

    #[Test]
    public function it_records_that_somebody_left(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'role' => 'Temp']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members/{$ryan->uuid}", [
            'left_on' => '2007-01-01',
            'left_precision' => 'year',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.left_on', '2007-01-01');
        $response->assertJsonPath('data.attributes.role', 'Temp');
        $response->assertJsonPath('data.attributes.is_current', false);
    }

    #[Test]
    public function it_refuses_a_departure_before_the_arrival(): void
    {
        [$user, $vault, $group] = $this->scrantonBranch();
        $ryan = GroupMember::factory()->create(['group_id' => $group->id, 'joined_on' => '2009-01-01', 'joined_precision' => 'year']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members/{$ryan->uuid}", ['left_on' => '2007-01-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('left_on');
    }

    #[Test]
    public function it_removes_a_membership_recorded_by_mistake(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->scrantonBranch();
        $membership = GroupMember::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/members/{$membership->uuid}")->assertNoContent();

        $this->assertSoftDeleted($membership);
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function scrantonBranch(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch'])];
    }
}
