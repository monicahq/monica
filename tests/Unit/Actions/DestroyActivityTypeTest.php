<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateActivityType;
use App\Actions\DestroyActivityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_type_the_product_did_not_supply(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $activityType = $this->activityType($user);

        new DestroyActivityType(user: $user, activityType: $activityType)->execute();

        $this->assertSoftDeleted($activityType);
    }

    #[Test]
    public function it_leaves_the_structure_where_it_is_so_a_restore_brings_the_fields_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $activityType = $this->activityType($user);
        $structure = $activityType->structure;

        new DestroyActivityType(user: $user, activityType: $activityType)->execute();

        $this->assertModelExists($structure);
        $this->assertNotNull($structure->refresh()->published_version_id);
    }

    #[Test]
    public function it_refuses_a_type_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $elsewhere = $this->activityType(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new DestroyActivityType(user: $user, activityType: $elsewhere)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new DestroyActivityType(user: $user, activityType: $this->activityType($user))->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ActivityTypeDeletion,
        );
    }

    #[Test]
    public function it_refuses_a_type_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $supplied = ActivityType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyActivityType(user: $user, activityType: $supplied)->execute();
    }

    private function activityType(User $user): ActivityType
    {
        return new CreateActivityType(
            user: $user,
            activityCategory: ActivityCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();
    }
}
