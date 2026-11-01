<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupRestorationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_deleted_group_back(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $group->delete();

        $this->actingAs($user)->put(route('groups.restoration.update', ['vault' => $vault, 'groupUuid' => $group->uuid]))
            ->assertRedirect(route('groups.show', ['vault' => $vault, 'group' => $group]))
            ->assertSessionHas('status', 'The group Finer things club is back.');

        $this->assertNotSoftDeleted($group);
    }

    #[Test]
    public function it_does_not_find_a_deleted_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $group->delete();

        $this->actingAs($user)->put(route('groups.restoration.update', ['vault' => $vault, 'groupUuid' => $group->uuid]))->assertNotFound();
    }
}
