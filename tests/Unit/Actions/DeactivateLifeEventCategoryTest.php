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
