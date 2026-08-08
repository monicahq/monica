<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEntityTypePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEntityTypePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_type_and_renumbers_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $companies = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $recipes = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        $moved = new UpdateEntityTypePosition(user: $user, entityType: $recipes, position: 1)->execute();

        $this->assertInstanceOf(EntityType::class, $moved);
        $this->assertSame(1, $recipes->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
        $this->assertSame(3, $companies->refresh()->position);
    }

    #[Test]
    public function it_puts_a_type_last_when_it_is_asked_for_a_position_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $companies = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateEntityTypePosition(user: $user, entityType: $animals, position: 99)->execute();

        $this->assertSame(1, $companies->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
    }

    #[Test]
    public function it_numbers_an_inactive_type_along_with_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $retired = EntityType::factory()->inactive()->create(['account_id' => $user->account_id, 'position' => 2]);
        $recipes = EntityType::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        new UpdateEntityTypePosition(user: $user, entityType: $recipes, position: 1)->execute();

        $this->assertSame(1, $recipes->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
        $this->assertSame(3, $retired->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_types_of_another_account_where_they_are(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $beets = EntityType::factory()->create(['account_id' => $dwight->account_id, 'position' => 1]);
        $animals = EntityType::factory()->create(['account_id' => $michael->account_id, 'position' => 1]);
        $recipes = EntityType::factory()->create(['account_id' => $michael->account_id, 'position' => 2]);

        new UpdateEntityTypePosition(user: $michael, entityType: $recipes, position: 1)->execute();

        $this->assertSame(1, $beets->refresh()->position);
        $this->assertSame(1, $recipes->refresh()->position);
        $this->assertSame(2, $animals->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $dwight->account_id, 'position' => 2]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new UpdateEntityTypePosition(user: $michael, entityType: $entityType, position: 1)->execute();
        } finally {
            $this->assertSame(2, $entityType->refresh()->position);
        }
    }

    #[Test]
    public function it_writes_the_move_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_plural' => 'Recipes',
            'position' => 1,
        ]);

        new UpdateEntityTypePosition(user: $user, entityType: $entityType, position: 1)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::EntityTypePositionUpdate
                && $job->parameters === ['name' => 'Recipes'];
        });
    }
}
