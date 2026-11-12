<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionFieldGroup;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_fields_of_a_removed_group_and_appends_them_after_the_standalone_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase']);
        $standaloneFirst = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        $standaloneSecond = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);
        $groupedSecond = PossessionField::factory()->inGroup($group)->create(['name' => 'Shop', 'position' => 2]);
        $groupedFirst = PossessionField::factory()->inGroup($group)->create(['name' => 'Price', 'position' => 1]);

        new DestroyPossessionFieldGroup(
            user: $user,
            group: $group,
        )->execute();

        $this->assertModelMissing($group);
        $this->assertDatabaseHas('possession_fields', ['id' => $groupedFirst->id, 'possession_field_group_id' => null, 'position' => 3]);
        $this->assertDatabaseHas('possession_fields', ['id' => $groupedSecond->id, 'possession_field_group_id' => null, 'position' => 4]);
        $this->assertDatabaseHas('possession_fields', ['id' => $standaloneFirst->id, 'position' => 1]);
        $this->assertDatabaseHas('possession_fields', ['id' => $standaloneSecond->id, 'position' => 2]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldGroupDeletion
                && $job->parameters === ['name' => 'Purchase', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $group = PossessionFieldGroup::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionFieldGroup(
            user: $user,
            group: $group,
        )->execute();
    }
}
