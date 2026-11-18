<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MovePossession;
use App\Enums\PossessionStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MovePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_closes_the_open_stay_and_opens_the_next_on_the_same_day(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Barn']);
        $cellar = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Cellar']);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press', 'current_location_id' => $barn->id]);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'location_id' => $barn->id, 'arrived_on' => '2019-11-08']);

        $stay = new MovePossession(
            user: $user,
            possession: $possession,
            location: $cellar,
            movedOn: CarbonImmutable::parse('2024-06-04'),
            reason: ' <b>Harvest season</b> ',
            note: 'Mose carried it down.',
        )->execute();

        $this->assertInstanceOf(PossessionLocationStay::class, $stay);
        $this->assertEquals('2024-06-04', $open->refresh()->left_on->toDateString());
        $this->assertDatabaseHas('possession_location_stays', [
            'id' => $stay->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'location_id' => $cellar->id,
            'left_on' => null,
            'reason' => 'Harvest season',
            'note' => 'Mose carried it down.',
        ]);
        $this->assertEquals('2024-06-04', $stay->arrived_on->toDateString());
        $this->assertEquals($cellar->id, $possession->refresh()->current_location_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionMove
                && $job->parameters === ['name' => 'Beet press', 'location' => 'Cellar'],
        );
    }

    #[Test]
    public function it_refuses_to_move_a_possession_that_was_stolen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id]);
        $cellar = Location::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'status' => PossessionStatusEnum::Stolen, 'current_location_id' => $barn->id]);

        try {
            new MovePossession(
                user: $user,
                possession: $possession,
                location: $cellar,
                movedOn: CarbonImmutable::parse('2024-06-04'),
            )->execute();
            $this->fail('A stolen possession was moved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('location_id', $exception->errors());
        }

        $this->assertEquals($barn->id, $possession->refresh()->current_location_id);
    }

    #[Test]
    public function it_opens_a_first_stay_when_none_is_open(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $cellar = Location::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => null]);

        $stay = new MovePossession(
            user: $user,
            possession: $possession,
            location: $cellar,
            movedOn: CarbonImmutable::parse('2024-06-04'),
        )->execute();

        $this->assertCount(1, $possession->locationStays);
        $this->assertTrue($possession->openLocationStay->is($stay));
        $this->assertEquals($cellar->id, $possession->refresh()->current_location_id);
    }

    #[Test]
    public function it_refuses_the_location_it_is_already_in(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Barn']);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $barn->id]);
        PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'location_id' => $barn->id]);

        try {
            new MovePossession(
                user: $user,
                possession: $possession,
                location: $barn,
                movedOn: CarbonImmutable::parse('2024-06-04'),
            )->execute();

            $this->fail('The move was accepted.');
        } catch (ValidationException $e) {
            $this->assertEquals(['location_id' => ['It is already in Barn.']], $e->errors());
        }

        $this->assertCount(1, $possession->locationStays);
    }

    #[Test]
    public function it_refuses_a_day_before_the_open_stay_began(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id]);
        $cellar = Location::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $barn->id]);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'location_id' => $barn->id, 'arrived_on' => '2019-11-08']);

        $this->expectException(ValidationException::class);

        try {
            new MovePossession(
                user: $user,
                possession: $possession,
                location: $cellar,
                movedOn: CarbonImmutable::parse('2019-11-07'),
            )->execute();
        } finally {
            $this->assertNull($open->refresh()->left_on);
            $this->assertEquals($barn->id, $possession->refresh()->current_location_id);
        }
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        new MovePossession(
            user: $user,
            possession: $possession,
            location: Location::factory()->create(),
            movedOn: CarbonImmutable::parse('2024-06-04'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MovePossession(
            user: $user,
            possession: Possession::factory()->create(),
            location: Location::factory()->create(['account_id' => $user->account_id]),
            movedOn: CarbonImmutable::parse('2024-06-04'),
        )->execute();
    }
}
