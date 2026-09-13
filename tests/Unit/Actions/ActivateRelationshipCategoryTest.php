<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateRelationshipCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_activates_the_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->inactive()->create(['account_id' => $user->account_id]);

        $settled = new ActivateRelationshipCategory(user: $user, relationshipCategory: $relationshipCategory)->execute();

        $this->assertInstanceOf(RelationshipCategory::class, $settled);
        $this->assertTrue($relationshipCategory->refresh()->is_active);
    }

    #[Test]
    public function it_leaves_the_category_where_it_was_in_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'position' => 4,
        ]);

        new ActivateRelationshipCategory(user: $user, relationshipCategory: $relationshipCategory)->execute();

        $this->assertSame(4, $relationshipCategory->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->inactive()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new ActivateRelationshipCategory(user: $michael, relationshipCategory: $relationshipCategory)->execute();
        } finally {
            $this->assertFalse($relationshipCategory->refresh()->is_active);
        }
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        new ActivateRelationshipCategory(user: $user, relationshipCategory: $relationshipCategory)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::RelationshipCategoryActivated
                && $job->parameters === ['name' => 'Dunder Mifflin'];
        });
    }
}
