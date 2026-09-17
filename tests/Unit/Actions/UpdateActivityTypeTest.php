<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateActivityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_name_the_description_and_the_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'name' => 'Coffee',
        ]);

        $updated = new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $category,
            name: 'Dundie awards',
            description: 'The ceremony Michael runs every year',
            icon: 'star',
            colour: '#a8447a',
        )->execute();

        $this->assertInstanceOf(ActivityType::class, $updated);
        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'name' => 'Dundie awards',
            'description' => 'The ceremony Michael runs every year',
            'icon' => 'star',
            'colour' => '#a8447a',
        ]);
    }

    #[Test]
    public function it_drops_the_supplied_wording_when_somebody_writes_their_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->supplied()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);

        new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $category,
            name: 'Phone call',
        )->execute();

        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'name' => 'Phone call',
            'name_translation_key' => null,
            'is_system' => true,
        ]);
    }

    #[Test]
    public function it_puts_a_type_last_in_the_category_it_moves_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $from = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $to = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $to->id,
            'position' => 4,
        ]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $from->id,
            'position' => 1,
        ]);

        new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $to,
            name: 'Warehouse visit',
        )->execute();

        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'activity_category_id' => $to->id,
            'position' => 5,
        ]);
    }

    #[Test]
    public function it_leaves_the_position_alone_when_the_category_does_not_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
            'position' => 3,
        ]);

        new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $category,
            name: 'Sales call',
        )->execute();

        $this->assertSame(3, $type->refresh()->position);
    }

    #[Test]
    public function it_strips_markup_from_the_name_and_the_description(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);

        new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $category,
            name: '  <b>Meeting</b>  ',
            description: '<i>In the annex</i>',
        )->execute();

        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'name' => 'Meeting',
            'description' => 'In the annex',
        ]);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $michael->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $dwight->account_id,
            'name' => 'Beet farming',
        ]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new UpdateActivityType(
                user: $michael,
                activityType: $type,
                activityCategory: $category,
                name: 'Stolen',
            )->execute();
        } finally {
            $this->assertSame('Beet farming', $type->refresh()->name);
        }
    }

    #[Test]
    public function it_refuses_a_category_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = ActivityType::factory()->create(['account_id' => $michael->account_id]);
        $category = ActivityCategory::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateActivityType(
            user: $michael,
            activityType: $type,
            activityCategory: $category,
            name: 'Anything',
        )->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = ActivityCategory::factory()->create(['account_id' => $user->account_id]);
        $type = ActivityType::factory()->create([
            'account_id' => $user->account_id,
            'activity_category_id' => $category->id,
        ]);

        new UpdateActivityType(
            user: $user,
            activityType: $type,
            activityCategory: $category,
            name: 'Party planning',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ActivityTypeUpdate
            && $job->parameters === ['name' => 'Party planning']);
    }
}
