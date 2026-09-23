<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyRelationshipCategory;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Exceptions\CannotDeleteARelationshipCategoryInUse;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_an_empty_category_the_user_added(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        new DestroyRelationshipCategory(user: $user, relationshipCategory: $category)->execute();

        $this->assertSoftDeleted($category);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipCategoryDeletion);
    }

    #[Test]
    public function it_refuses_to_delete_a_supplied_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $this->expectException(CannotChangeASuppliedRelationshipCategory::class);

        new DestroyRelationshipCategory(user: $user, relationshipCategory: $category)->execute();
    }

    #[Test]
    public function it_refuses_to_delete_a_category_that_still_holds_a_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);

        $this->expectException(CannotDeleteARelationshipCategoryInUse::class);

        new DestroyRelationshipCategory(user: $user, relationshipCategory: $category)->execute();
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyRelationshipCategory(user: $michael, relationshipCategory: $category)->execute();
    }
}
