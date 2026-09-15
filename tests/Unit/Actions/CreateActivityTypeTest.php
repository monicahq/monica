<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateActivityType;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_nature_an_activity_can_be_recorded_as(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $activityType = new CreateActivityType(
            user: $user,
            activityCategory: $category,
            name: 'Restaurant',
            description: 'Where we went and what we ate',
        )->execute();

        $this->assertInstanceOf(ActivityType::class, $activityType);

        $this->assertDatabaseHas('activity_types', [
            'id' => $activityType->id,
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'name' => 'Restaurant',
            'description' => 'Where we went and what we ate',
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    #[Test]
    public function it_gives_the_type_a_structure_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $activityType = new CreateActivityType(user: $user, activityCategory: $category, name: 'Concert')->execute();

        $this->assertDatabaseHas('structures', [
            'account_id' => $user->account_id,
            'owner_type' => ActivityType::class,
            'owner_id' => $activityType->id,
            'applies_to' => StructureAppliesToEnum::Activity->value,
            'entity_type_id' => null,
        ]);
    }

    #[Test]
    public function it_publishes_a_first_version_so_an_activity_can_be_recorded_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $activityType = new CreateActivityType(user: $user, activityCategory: $category, name: 'Call')->execute();
        $version = $activityType->structure->publishedVersion;

        $this->assertNotNull($version);
        $this->assertEquals(VersionStatusEnum::Published, $version->status);
        $this->assertEquals(1, $version->version_number);
    }

    #[Test]
    public function it_starts_the_type_with_one_page_and_no_custom_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        $activityType = new CreateActivityType(user: $user, activityCategory: $category, name: 'Walk')->execute();
        $version = $activityType->structure->publishedVersion;

        $this->assertCount(1, $version->pages);
        $this->assertCount(0, $version->assembly);
    }

    #[Test]
    public function it_puts_a_new_type_at_the_end_of_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        new CreateActivityType(user: $user, activityCategory: $category, name: 'Lunch')->execute();
        $second = new CreateActivityType(user: $user, activityCategory: $category, name: 'Dinner')->execute();

        $this->assertEquals(2, $second->position);
    }

    #[Test]
    public function it_refuses_a_category_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateActivityType(user: $user, activityCategory: $category, name: 'Trip')->execute();
    }

    #[Test]
    public function it_writes_the_creation_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        new CreateActivityType(user: $user, activityCategory: $category, name: 'Hike')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ActivityTypeCreation,
        );
    }
}
