<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionCondition;
use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_condition_after_the_others_in_blue_without_an_icon(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Used', 'position' => 2]);

        $possessionCondition = new CreatePossessionCondition(
            user: $user,
            name: 'Mint',
        )->execute();

        $this->assertInstanceOf(PossessionCondition::class, $possessionCondition);
        $this->assertDatabaseHas('possession_conditions', [
            'id' => $possessionCondition->id,
            'account_id' => $user->account_id,
            'name' => 'Mint',
            'colour' => VaultColourEnum::Blue->value,
            'icon' => null,
            'position' => 3,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionConditionCreation
                && $job->parameters === ['name' => 'Mint'],
        );
    }

    #[Test]
    public function it_starts_the_order_at_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $possessionCondition = new CreatePossessionCondition(
            user: $user,
            name: 'Mint',
            colour: VaultColourEnum::Teal,
            icon: 'star',
        )->execute();

        $this->assertDatabaseHas('possession_conditions', [
            'id' => $possessionCondition->id,
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'star',
            'position' => 1,
        ]);
    }

    #[Test]
    public function it_stores_an_empty_icon_as_none(): void
    {
        Queue::fake();

        $possessionCondition = new CreatePossessionCondition(
            user: User::factory()->create(),
            name: 'Mint',
            icon: '',
        )->execute();

        $this->assertNull($possessionCondition->icon);
    }

    #[Test]
    public function it_refuses_an_icon_outside_the_catalogue(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePossessionCondition(
            user: User::factory()->create(),
            name: 'Mint',
            icon: 'dundie',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_name_left_empty_once_sanitised(): void
    {
        $this->expectException(ValidationException::class);

        new CreatePossessionCondition(
            user: User::factory()->create(),
            name: '  <b></b> ',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_name_the_account_already_has_ignoring_case(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint']);

        try {
            new CreatePossessionCondition(user: $user, name: 'mint')->execute();
            $this->fail('A duplicate name was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
        }
    }

    #[Test]
    public function it_accepts_a_name_another_account_has(): void
    {
        Queue::fake();

        PossessionCondition::factory()->create(['name' => 'Mint']);

        $possessionCondition = new CreatePossessionCondition(
            user: User::factory()->create(),
            name: 'Mint',
        )->execute();

        $this->assertEquals(1, $possessionCondition->position);
    }
}
