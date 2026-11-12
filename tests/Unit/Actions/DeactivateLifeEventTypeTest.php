<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateLifeEventType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_whether_the_type_is_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundie awards']);

        $returned = new DeactivateLifeEventType(user: $user, lifeEventType: $type)->execute();

        $this->assertInstanceOf(LifeEventType::class, $returned);
        $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => false]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventTypeDeactivated
            && $job->parameters === ['name' => 'Dundie awards']);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateLifeEventType(user: $michael, lifeEventType: $type)->execute();
        } finally {
            $this->assertDatabaseHas('life_event_types', ['id' => $type->id, 'is_active' => true]);
        }
    }
}
