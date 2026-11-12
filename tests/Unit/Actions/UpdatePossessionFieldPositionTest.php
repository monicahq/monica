<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionFieldPosition;
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

class UpdatePossessionFieldPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_third_field_to_the_first_position_and_numbers_the_set_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $first = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number', 'position' => 1]);
        $second = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Power', 'position' => 2]);
        $third = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Shop', 'position' => 3]);

        $moved = new UpdatePossessionFieldPosition(
            user: $user,
            field: $third,
            position: 1,
        )->execute();

        $this->assertInstanceOf(PossessionField::class, $moved);
        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldUpdate
                && $job->parameters === ['name' => 'Shop', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_reorders_a_grouped_field_among_its_group_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        $standaloneFirst = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        $standaloneSecond = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);
        $groupedFirst = PossessionField::factory()->inGroup($group)->create(['position' => 1]);
        $groupedSecond = PossessionField::factory()->inGroup($group)->create(['position' => 2]);

        new UpdatePossessionFieldPosition(
            user: $user,
            field: $groupedSecond,
            position: 1,
        )->execute();

        $this->assertSame(1, $groupedSecond->refresh()->position);
        $this->assertSame(2, $groupedFirst->refresh()->position);
        $this->assertSame(1, $standaloneFirst->refresh()->position);
        $this->assertSame(2, $standaloneSecond->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_field_of_another_account(): void
    {
        $user = User::factory()->create();
        $field = PossessionField::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionFieldPosition(
            user: $user,
            field: $field,
            position: 1,
        )->execute();
    }
}
