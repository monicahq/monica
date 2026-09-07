<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRelationshipCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_category_at_the_end_of_the_list(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        $category = new CreateRelationshipCategory(user: $user, name: 'Neighbourhood')->execute();

        $this->assertInstanceOf(RelationshipCategory::class, $category);
        $this->assertDatabaseHas('relationship_categories', [
            'id' => $category->id,
            'account_id' => $user->account_id,
            'name' => 'Neighbourhood',
            'is_system' => false,
            'position' => 3,
        ]);
        $this->assertNull($category->key);
    }

    #[Test]
    public function it_writes_the_addition_to_the_journal(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        new CreateRelationshipCategory(user: $user, name: 'Neighbourhood')->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipCategoryCreation);
    }
}
