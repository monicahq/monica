<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolvesModelInVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_model_naming_one_column_resolves_by_it(): void
    {
        $user = User::factory()->create();
        $entity = $this->entityOf($user);

        $this->actingAs($user);

        $this->assertTrue($entity->is(new Entity()->resolveRouteBinding($entity->uuid)));
    }

    #[Test]
    public function it_still_accepts_the_primary_key_as_a_second_spelling(): void
    {
        $user = User::factory()->create();
        $entity = $this->entityOf($user);

        $this->actingAs($user);

        $this->assertTrue($entity->is(new Entity()->resolveRouteBinding((string) $entity->id)));
    }

    #[Test]
    public function it_still_honours_a_column_the_route_names_outright(): void
    {
        $user = User::factory()->create();
        $entity = $this->entityOf($user);

        $this->actingAs($user);

        $this->assertTrue($entity->is(new Entity()->resolveRouteBinding($entity->uuid, 'uuid')));
    }

    #[Test]
    public function it_still_refuses_a_record_of_another_account(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();

        $this->actingAs($user);

        $this->assertNotSame($user->account_id, $entity->account_id);
        $this->assertNull(new Entity()->resolveRouteBinding($entity->uuid));
    }

    private function entityOf(User $user): Entity
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
    }
}
