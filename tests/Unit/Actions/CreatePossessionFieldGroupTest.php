<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionFieldGroup;
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

class CreatePossessionFieldGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_untitled_group_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 1]);
        PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'position' => 2]);

        $group = new CreatePossessionFieldGroup(
            user: $user,
            possessionType: $possessionType,
        )->execute();

        $this->assertInstanceOf(PossessionFieldGroup::class, $group);
        $this->assertDatabaseHas('possession_field_groups', [
            'id' => $group->id,
            'account_id' => $user->account_id,
            'possession_type_id' => $possessionType->id,
            'position' => 3,
        ]);
        $this->assertNotSame('', $group->name);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldGroupCreation
                && $job->parameters === ['name' => $group->name, 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_adds_a_group_with_the_name_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $group = new CreatePossessionFieldGroup(
            user: $user,
            possessionType: $possessionType,
            name: '  Purchase  ',
        )->execute();

        $this->assertSame('Purchase', $group->name);
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new CreatePossessionFieldGroup(
            user: $user,
            possessionType: $possessionType,
            name: '   ',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionFieldGroup(
            user: $user,
            possessionType: $possessionType,
        )->execute();
    }
}
