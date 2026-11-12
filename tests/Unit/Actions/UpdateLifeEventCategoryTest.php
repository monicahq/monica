<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateLifeEventCategory;
use App\Enums\LifeEventCategoryEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Volunteering']);

        $updated = new UpdateLifeEventCategory(user: $user, lifeEventCategory: $category, name: 'Bénévolat')->execute();

        $this->assertInstanceOf(LifeEventCategory::class, $updated);
        $this->assertEquals('Bénévolat', $category->refresh()->name);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventCategoryUpdate
                && $job->parameters === ['name' => 'Bénévolat'];
        });
    }

    #[Test]
    public function it_renames_a_supplied_category_because_being_supplied_governs_deletion_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->system(LifeEventCategoryEnum::HealthAndWellbeing)->create([
            'account_id' => $user->account_id,
        ]);

        new UpdateLifeEventCategory(user: $user, lifeEventCategory: $category, name: 'Santé')->execute();

        $category->refresh();

        $this->assertEquals('Santé', $category->name);
        $this->assertTrue($category->is_system);
        $this->assertEquals('health_and_wellbeing', $category->key);
        $this->assertNull($category->name_translation_key);
    }

    #[Test]
    public function it_reduces_the_name_to_plain_text(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        new UpdateLifeEventCategory(user: $user, lifeEventCategory: $category, name: '<b>Bénévolat</b>')->execute();

        $this->assertEquals('Bénévolat', $category->refresh()->name);
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Volunteering']);

        $this->expectException(ModelNotFoundException::class);

        try {
            new UpdateLifeEventCategory(user: $michael, lifeEventCategory: $category, name: 'Bénévolat')->execute();
        } finally {
            $this->assertEquals('Volunteering', $category->refresh()->name);
        }
    }
}
