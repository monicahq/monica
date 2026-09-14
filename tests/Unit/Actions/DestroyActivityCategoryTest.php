<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyActivityCategory;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteASuppliedActivityCategory;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyActivityCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_category_the_account_added(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        new DestroyActivityCategory(user: $user, activityCategory: $category)->execute();

        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_to_delete_a_supplied_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $this->expectException(CannotDeleteASuppliedActivityCategory::class);

        try {
            new DestroyActivityCategory(user: $user, activityCategory: $category)->execute();
        } finally {
            $this->assertNotSoftDeleted($category);
        }
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DestroyActivityCategory(user: $michael, activityCategory: $category)->execute();
        } finally {
            $this->assertNotSoftDeleted($category);
        }
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering']);

        new DestroyActivityCategory(user: $user, activityCategory: $category)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ActivityCategoryDeletion
                && $job->parameters === ['name' => 'Volunteering'];
        });
    }
}
