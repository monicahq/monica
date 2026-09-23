<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateLifeEventType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_whether_the_type_is_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'Dundie awards']);

        $returned = new ActivateLifeEventType(user: $user, lifeEventType: $type)->execute();

        $this->assertInstanceOf(LifeEventType::class, $returned);
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => true]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventTypeActivated
            && $job->parameters === ['name' => 'Dundie awards']);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->inactive()->create(['account_id' => $user->account_id]);

        new ActivateLifeEventType(user: $user, lifeEventType: $type)->execute();
        new ActivateLifeEventType(user: $user, lifeEventType: $type)->execute();

        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => true]);
    }

    #[Test]
    public function it_leaves_the_position_and_the_name_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'name' => 'Sales call',
            'position' => 4,
        ]);

        new ActivateLifeEventType(user: $user, lifeEventType: $type)->execute();

        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'name' => 'Sales call', 'position' => 4]);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = LifeEventType::factory()->inactive()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new ActivateLifeEventType(user: $michael, lifeEventType: $type)->execute();
        } finally {
            $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => false]);
        }
    }
}
