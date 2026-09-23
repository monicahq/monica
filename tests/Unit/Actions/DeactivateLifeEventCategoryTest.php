<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateLifeEventCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deactivates_the_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Volunteering',
        ]);

        $settled = new DeactivateLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();

        $this->assertInstanceOf(LifeEventCategory::class, $settled);
        $this->assertFalse($category->refresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventCategoryDeactivated
                && $job->parameters === ['name' => 'Volunteering'];
        });
    }

    #[Test]
    public function it_deactivates_a_supplied_category_like_any_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id]);

        new DeactivateLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();

        $category->refresh();

        $this->assertFalse($category->is_active);
        $this->assertTrue($category->is_system);
    }

    #[Test]
    public function it_leaves_the_category_where_it_was_in_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $user->account_id,
            'position' => 4,
        ]);

        new DeactivateLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();

        $this->assertSame(4, $category->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateLifeEventCategory(user: $michael, lifeEventCategory: $category)->execute();
        } finally {
            $this->assertTrue($category->refresh()->is_active);
        }
    }
}
