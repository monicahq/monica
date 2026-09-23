<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRelationshipCategoryPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipCategoryPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_category_up(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        $second = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Romantic', 'position' => 2]);

        new UpdateRelationshipCategoryPosition(user: $user, relationshipCategory: $second, position: 1)->execute();

        $this->assertEquals(1, $second->refresh()->position);
        $this->assertEquals(2, $first->refresh()->position);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipCategoryPositionUpdate);
    }

    #[Test]
    public function it_moves_a_category_down(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        $second = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Romantic', 'position' => 2]);
        $third = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Other', 'position' => 3]);

        new UpdateRelationshipCategoryPosition(user: $user, relationshipCategory: $first, position: 2)->execute();

        $this->assertEquals(1, $second->refresh()->position);
        $this->assertEquals(2, $first->refresh()->position);
        $this->assertEquals(3, $third->refresh()->position);
    }

    #[Test]
    public function a_position_beyond_the_list_lands_at_its_end(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateRelationshipCategoryPosition(user: $user, relationshipCategory: $first, position: 99)->execute();

        $this->assertEquals(2, $first->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationshipCategoryPosition(user: $michael, relationshipCategory: $category, position: 1)->execute();
    }
}
