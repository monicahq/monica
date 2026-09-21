<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_nature_a_life_event_can_be_recorded_as(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $lifeEventType = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: $category,
            name: 'Restaurant',
            description: 'Where we went and what we ate',
        )->execute();

        $this->assertInstanceOf(LifeEventType::class, $lifeEventType);

        $this->assertDatabaseHas('life_event_types', [
            'id' => $lifeEventType->id,
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
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
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $lifeEventType = new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Concert')->execute();

        $this->assertDatabaseHas('structures', [
            'account_id' => $user->account_id,
            'owner_type' => LifeEventType::class,
            'owner_id' => $lifeEventType->id,
            'applies_to' => StructureAppliesToEnum::LifeEvent->value,
            'entity_type_id' => null,
        ]);
    }

    #[Test]
    public function it_publishes_a_first_version_so_a_life_event_can_be_recorded_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $lifeEventType = new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Call')->execute();
        $version = $lifeEventType->structure->publishedVersion;

        $this->assertNotNull($version);
        $this->assertEquals(VersionStatusEnum::Published, $version->status);
        $this->assertEquals(1, $version->version_number);
    }

    #[Test]
    public function it_starts_the_type_with_one_page_and_no_custom_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $lifeEventType = new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Walk')->execute();
        $version = $lifeEventType->structure->publishedVersion;

        $this->assertCount(1, $version->pages);
        $this->assertCount(0, $version->assembly);
    }

    #[Test]
    public function it_puts_a_new_type_at_the_end_of_its_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Lunch')->execute();
        $second = new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Dinner')->execute();

        $this->assertEquals(2, $second->position);
    }

    #[Test]
    public function it_refuses_a_category_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Trip')->execute();
    }

    #[Test]
    public function it_writes_the_creation_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        new CreateLifeEventType(user: $user, lifeEventCategory: $category, name: 'Hike')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventTypeCreation,
        );
    }
}
