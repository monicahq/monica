<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionCondition;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_recolours_and_gives_an_icon_to_a_condition_in_its_place(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn', 'position' => 4]);

        $updated = new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: 'Well loved',
            colour: VaultColourEnum::Amber,
            icon: 'heart',
        )->execute();

        $this->assertInstanceOf(PossessionCondition::class, $updated);
        $this->assertDatabaseHas('possession_conditions', [
            'id' => $possessionCondition->id,
            'name' => 'Well loved',
            'colour' => VaultColourEnum::Amber->value,
            'icon' => 'heart',
            'position' => 4,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionConditionUpdate
                && $job->parameters === ['name' => 'Well loved'],
        );
    }

    #[Test]
    public function it_removes_the_icon(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'icon' => 'star']);

        new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: 'Mint',
            colour: VaultColourEnum::Teal,
            icon: null,
        )->execute();

        $this->assertNull($possessionCondition->refresh()->icon);
    }

    #[Test]
    public function it_accepts_a_change_of_case_of_its_own_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Used']);

        new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: 'USED',
            colour: VaultColourEnum::Blue,
        )->execute();

        $this->assertEquals('USED', $possessionCondition->refresh()->name);
    }

    #[Test]
    public function it_refuses_the_name_of_another_condition_ignoring_case(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Broken']);
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Damaged']);

        $this->expectException(ValidationException::class);

        new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: 'broken',
            colour: VaultColourEnum::Rust,
        )->execute();
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: ' ',
            colour: VaultColourEnum::Blue,
        )->execute();
    }

    #[Test]
    public function it_refuses_an_icon_outside_the_catalogue(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionCondition(
            user: $user,
            possessionCondition: $possessionCondition,
            name: 'Mint',
            colour: VaultColourEnum::Blue,
            icon: 'dundie',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_condition_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionCondition(
            user: User::factory()->create(),
            possessionCondition: PossessionCondition::factory()->create(),
            name: 'Mint',
            colour: VaultColourEnum::Blue,
        )->execute();
    }
}
