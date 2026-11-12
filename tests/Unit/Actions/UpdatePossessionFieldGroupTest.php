<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionFieldGroup;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Untitled group']);

        $updated = new UpdatePossessionFieldGroup(
            user: $user,
            group: $group,
            name: 'Purchase',
        )->execute();

        $this->assertInstanceOf(PossessionFieldGroup::class, $updated);
        $this->assertDatabaseHas('possession_field_groups', [
            'id' => $group->id,
            'name' => 'Purchase',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldGroupUpdate
                && $job->parameters === ['name' => 'Purchase', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionFieldGroup(
            user: $user,
            group: $group,
            name: '   ',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $group = PossessionFieldGroup::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionFieldGroup(
            user: $user,
            group: $group,
            name: 'Purchase',
        )->execute();
    }
}
