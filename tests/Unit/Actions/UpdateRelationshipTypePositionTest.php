<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRelationshipTypePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_up_inside_its_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $first = $this->typeIn($user, $category, 'Sibling', 1);
        $second = $this->typeIn($user, $category, 'Cousin', 2);

        new UpdateRelationshipTypePosition(user: $user, relationshipType: $second, position: 1)->execute();

        $this->assertEquals(1, $second->refresh()->position);
        $this->assertEquals(2, $first->refresh()->position);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipTypePositionUpdate);
    }

    #[Test]
    public function it_moves_a_type_down_inside_its_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $first = $this->typeIn($user, $category, 'Sibling', 1);
        $second = $this->typeIn($user, $category, 'Cousin', 2);
        $third = $this->typeIn($user, $category, 'Nephew', 3);

        new UpdateRelationshipTypePosition(user: $user, relationshipType: $first, position: 2)->execute();

        $this->assertEquals(1, $second->refresh()->position);
        $this->assertEquals(2, $first->refresh()->position);
        $this->assertEquals(3, $third->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_other_categories_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $family = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $work = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $sibling = $this->typeIn($user, $family, 'Sibling', 1);
        $this->typeIn($user, $family, 'Cousin', 2);
        $colleague = $this->typeIn($user, $work, 'Colleague', 1);

        new UpdateRelationshipTypePosition(user: $user, relationshipType: $sibling, position: 2)->execute();

        $this->assertEquals(1, $colleague->refresh()->position);
    }

    #[Test]
    public function a_position_beyond_the_category_lands_at_its_end(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        $first = $this->typeIn($user, $category, 'Sibling', 1);
        $this->typeIn($user, $category, 'Cousin', 2);

        new UpdateRelationshipTypePosition(user: $user, relationshipType: $first, position: 99)->execute();

        $this->assertEquals(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationshipTypePosition(user: $michael, relationshipType: $type, position: 1)->execute();
    }

    private function typeIn(User $user, RelationshipCategory $category, string $name, int $position): RelationshipType
    {
        return RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => $name,
            'position' => $position,
        ]);
    }
}
