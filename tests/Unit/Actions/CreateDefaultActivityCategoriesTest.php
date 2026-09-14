<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultActivityCategories;
use App\Enums\ActivityCategoryEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateDefaultActivityCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_the_eight_supplied_categories(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $categories = new CreateDefaultActivityCategories(user: $user)->execute();

        $this->assertCount(8, $categories);
        $this->assertInstanceOf(ActivityCategory::class, $categories[0]);
        $this->assertEquals(8, ActivityCategory::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_files_them_in_the_order_the_product_gives_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultActivityCategories(user: $user)->execute();

        $keys = ActivityCategory::query()
            ->where('account_id', $user->account_id)
            ->orderBy('position')
            ->pluck('key')
            ->all();

        $this->assertEquals(array_map(fn (ActivityCategoryEnum $case): string => $case->value, ActivityCategoryEnum::cases()), $keys);
        $this->assertEquals('other', $keys[7]);
    }

    #[Test]
    public function it_marks_every_one_as_supplied_and_leaves_the_wording_to_the_translator(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultActivityCategories(user: $user)->execute();

        $first = ActivityCategory::query()->where('account_id', $user->account_id)->orderBy('position')->first();

        $this->assertDatabaseHas('activity_categories', [
            'account_id' => $user->account_id,
            'key' => 'relationships_and_family',
            'name' => null,
            'name_translation_key' => 'Relationships and family',
            'is_system' => true,
            'is_active' => true,
            'position' => 1,
        ]);
        $this->assertEquals(__('Relationships and family'), $first->name);
    }

    #[Test]
    public function it_gives_each_account_its_own_set(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        new CreateDefaultActivityCategories(user: $michael)->execute();
        new CreateDefaultActivityCategories(user: $dwight)->execute();

        $this->assertEquals(8, ActivityCategory::query()->where('account_id', $michael->account_id)->count());
        $this->assertEquals(8, ActivityCategory::query()->where('account_id', $dwight->account_id)->count());
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultActivityCategories(user: $user)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ActivityCategoriesSeeded;
        });
    }
}
