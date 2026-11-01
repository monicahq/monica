<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'name',
            'description',
            'started_on',
            'started_precision',
            'ended_on',
            'ended_precision',
            'current_member_count',
            'created_at',
            'updated_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_groups_of_the_vault_and_leaves_other_vaults_out(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $household = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Halpert household']);
        GroupMember::factory()->count(3)->create(['group_id' => $household->id]);
        Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        Group::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Halpert household');
        $response->assertJsonPath('data.0.attributes.current_member_count', 3);
    }

    #[Test]
    public function it_creates_a_group(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups", [
            'name' => 'Scranton branch',
            'started_on' => '1990-01-01',
            'started_precision' => 'decade',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.name', 'Scranton branch');
        $response->assertJsonPath('data.attributes.started_precision', 'decade');
        $response->assertJsonPath('data.attributes.current_member_count', 0);
    }

    #[Test]
    public function it_refuses_a_group_without_a_name(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_keeps_what_an_update_leaves_out(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch', 'description' => 'Before the merger']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}", ['name' => 'Scranton branch']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Scranton branch');
        $response->assertJsonPath('data.attributes.description', 'Before the merger');
    }

    #[Test]
    public function it_deletes_a_group(): void
    {
        Queue::fake();
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}")->assertNoContent();

        $this->assertSoftDeleted($group);
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_while_groups_are_off(): void
    {
        [$user, $vault] = $this->vaultOfSomebody();
        $user->account->features()->deactivate('groups');
        Feature::flushCache();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups")->assertNotFound();
    }

    /** @return array{0: User, 1: Vault} */
    private function vaultOfSomebody(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
