<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateRelationshipCategory;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_category_the_user_added(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        new UpdateRelationshipCategory(user: $user, relationshipCategory: $category, name: 'Around the block')->execute();

        $this->assertDatabaseHas('relationship_categories', [
            'id' => $category->id,
            'name' => 'Around the block',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipCategoryUpdate);
    }

    #[Test]
    public function it_refuses_to_rename_a_supplied_category(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $this->expectException(CannotChangeASuppliedRelationshipCategory::class);

        new UpdateRelationshipCategory(user: $user, relationshipCategory: $category, name: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateRelationshipCategory(user: $michael, relationshipCategory: $category, name: 'Beet farming')->execute();
    }
}
