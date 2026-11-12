<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_a_type_nobody_uses(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $type));
    }

    #[Test]
    public function it_refuses_deleting_a_type_the_product_supplied(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id, 'is_system' => true]);

        $response = Gate::forUser($user)->inspect('delete', $type);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_refuses_deleting_a_type_relationships_are_written_with(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        Relationship::factory()->create([
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
        ]);

        $response = Gate::forUser($user)->inspect('delete', $type);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(Gate::forUser(null)->allows('delete', $type));
    }
}
