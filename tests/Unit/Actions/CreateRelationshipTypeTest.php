<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRelationshipType;
use App\Enums\UserActionEnum;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_directed_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'Godparent / Godchild',
            roleA: 'Godparent',
            roleB: 'Godchild',
            isSymmetric: false,
        )->execute();

        $this->assertInstanceOf(RelationshipType::class, $type);
        $this->assertDatabaseHas('relationship_types', [
            'id' => $type->id,
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Godparent / Godchild',
            'role_a' => 'Godparent',
            'role_b' => 'Godchild',
            'is_symmetric' => false,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function a_symmetric_type_carries_the_same_word_on_both_sides(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'Neighbour',
            roleA: 'Neighbour',
            roleB: 'Anything else',
            isSymmetric: true,
        )->execute();

        $this->assertEquals('Neighbour', $type->role_a);
        $this->assertEquals('Neighbour', $type->role_b);
    }

    #[Test]
    public function a_type_somebody_invents_carries_no_key_and_no_behaviour(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $type = new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'Parent',
            roleA: 'Parent',
            roleB: 'Parent',
        )->execute();

        $this->assertNull($type->key);
        $this->assertNull($type->system_behavior);
        $this->assertFalse($type->is_system);
    }

    #[Test]
    public function it_goes_at_the_end_of_its_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Sibling',
            'position' => 3,
        ]);

        $type = new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'Cousin',
            roleA: 'Cousin',
            roleB: 'Cousin',
        )->execute();

        $this->assertEquals(4, $type->position);
    }

    #[Test]
    public function it_refuses_a_name_the_account_already_uses(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'name' => 'Cousin']);

        $this->expectException(RelationshipTypeNameIsAlreadyUsed::class);

        new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'cousin',
            roleA: 'Cousin',
            roleB: 'Cousin',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateRelationshipType(
            user: $michael,
            relationshipCategory: $category,
            name: 'Beet buddy',
            roleA: 'Beet buddy',
            roleB: 'Beet buddy',
        )->execute();
    }

    #[Test]
    public function it_writes_the_addition_to_the_journal(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        new CreateRelationshipType(
            user: $user,
            relationshipCategory: $category,
            name: 'Neighbour',
            roleA: 'Neighbour',
            roleB: 'Neighbour',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipTypeCreation);
    }
}
