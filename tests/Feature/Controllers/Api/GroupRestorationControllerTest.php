<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupRestorationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_a_deleted_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $group->delete();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/restoration");

        $response->assertOk();
        $response->assertJsonPath('data.id', $group->uuid);
        $response->assertJsonPath('data.attributes.deleted_at', null);
        $this->assertNotSoftDeleted($group);
    }

    #[Test]
    public function it_does_not_find_a_group_that_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/restoration")->assertNotFound();
    }
}
