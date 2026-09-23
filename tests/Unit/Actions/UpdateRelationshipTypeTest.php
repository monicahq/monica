<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRelationshipType;
use App\Enums\RelationshipBehaviorEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeARelationshipTypeDirection;
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

class UpdateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_rewords_a_type_the_user_invented(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Mentor / Mentee',
            roleA: 'Mentor',
            roleB: 'Mentee',
            isSymmetric: false,
            isActive: true,
        )->execute();

        $this->assertDatabaseHas('relationship_types', [
            'id' => $type->id,
            'name' => 'Mentor / Mentee',
            'role_a' => 'Mentor',
            'role_b' => 'Mentee',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipTypeUpdate);
    }

    #[Test]
    public function a_supplied_type_can_be_reworded_and_moved_and_keeps_what_it_means(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $family = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);
        $other = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Chosen family']);
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $family->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $other,
            name: 'Parent / Enfant',
            roleA: 'Papa ou maman',
            roleB: 'Enfant',
            isSymmetric: false,
            isActive: true,
        )->execute();

        $type->refresh();

        $this->assertEquals('Parent / Enfant', $type->name);
        $this->assertEquals('Papa ou maman', $type->role_a);
        $this->assertTrue($other->is($type->relationshipCategory));
        $this->assertTrue($type->is_system);
        $this->assertEquals('parent_child', $type->key);
        $this->assertEquals(RelationshipBehaviorEnum::GenealogyParentage, $type->system_behavior);
    }

    #[Test]
    public function a_symmetric_type_keeps_the_same_word_on_both_sides(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Flatmate',
            roleA: 'Flatmate',
            roleB: 'Something else entirely',
            isSymmetric: true,
            isActive: true,
        )->execute();

        $type->refresh();

        $this->assertEquals('Flatmate', $type->role_a);
        $this->assertEquals('Flatmate', $type->role_b);
    }

    #[Test]
    public function it_refuses_to_turn_a_supplied_type_round(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->expectException(CannotChangeARelationshipTypeDirection::class);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Parent / Child',
            roleA: 'Parent',
            roleB: 'Parent',
            isSymmetric: true,
            isActive: true,
        )->execute();
    }

    #[Test]
    public function a_type_nobody_uses_may_be_turned_round(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Godparent / Godchild',
            roleA: 'Godsibling',
            roleB: 'Godsibling',
            isSymmetric: true,
            isActive: true,
        )->execute();

        $this->assertTrue($type->refresh()->is_symmetric);
    }

    #[Test]
    public function rewording_a_role_never_swaps_the_two_people(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->directed()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Godparent / Godchild',
            roleA: 'Godchild',
            roleB: 'Godparent',
            isSymmetric: false,
            isActive: true,
        )->execute();

        $type->refresh();

        $this->assertEquals('Godchild', $type->role_a);
        $this->assertEquals('Godparent', $type->role_b);
    }

    #[Test]
    public function it_retires_a_type_without_touching_what_is_written(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Neighbour',
            roleA: 'Neighbour',
            roleB: 'Neighbour',
            isSymmetric: true,
            isActive: false,
        )->execute();

        $this->assertFalse($type->refresh()->is_active);
    }

    #[Test]
    public function it_refuses_a_name_another_type_already_uses(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'name' => 'Cousin']);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Neighbour',
        ]);

        $this->expectException(RelationshipTypeNameIsAlreadyUsed::class);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Cousin',
            roleA: 'Cousin',
            roleB: 'Cousin',
            isSymmetric: true,
            isActive: true,
        )->execute();
    }

    #[Test]
    public function it_lets_a_type_keep_its_own_name(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Neighbour',
        ]);

        new UpdateRelationshipType(
            user: $user,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Neighbour',
            roleA: 'Next door',
            roleB: 'Next door',
            isSymmetric: true,
            isActive: true,
        )->execute();

        $this->assertEquals('Next door', $type->refresh()->role_a);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $michael->account_id]);
        $type = RelationshipType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationshipType(
            user: $michael,
            relationshipType: $type,
            relationshipCategory: $category,
            name: 'Beet buddy',
            roleA: 'Beet buddy',
            roleB: 'Beet buddy',
            isSymmetric: true,
            isActive: true,
        )->execute();
    }
}
