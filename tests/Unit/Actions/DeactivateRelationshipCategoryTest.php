<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateRelationshipCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deactivates_the_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $settled = new DeactivateRelationshipCategory(user: $user, relationshipCategory: $relationshipCategory)->execute();

        $this->assertInstanceOf(RelationshipCategory::class, $settled);
        $this->assertFalse($relationshipCategory->refresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::RelationshipCategoryDeactivated
                && $job->parameters === ['name' => 'Dunder Mifflin'];
        });
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $relationshipCategory = RelationshipCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateRelationshipCategory(user: $michael, relationshipCategory: $relationshipCategory)->execute();
        } finally {
            $this->assertTrue($relationshipCategory->refresh()->is_active);
        }
    }
}
