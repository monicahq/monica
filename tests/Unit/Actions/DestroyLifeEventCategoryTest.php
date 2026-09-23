<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyLifeEventCategory;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_category_the_account_added(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering']);

        new DestroyLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();

        $this->assertSoftDeleted($category);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventCategoryDeletion
                && $job->parameters === ['name' => 'Volunteering'];
        });
    }

    #[Test]
    public function it_refuses_to_delete_a_supplied_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        try {
            new DestroyLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();
        } finally {
            $this->assertNotSoftDeleted($category);
        }
    }

    #[Test]
    public function it_refuses_to_delete_a_category_still_holding_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        $this->expectException(AuthorizationException::class);

        try {
            new DestroyLifeEventCategory(user: $user, lifeEventCategory: $category)->execute();
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
        $category = LifeEventCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DestroyLifeEventCategory(user: $michael, lifeEventCategory: $category)->execute();
        } finally {
            $this->assertNotSoftDeleted($category);
        }
    }
}
