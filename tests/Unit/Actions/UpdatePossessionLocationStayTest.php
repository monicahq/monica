<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionLocationStay;
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

class UpdatePossessionLocationStayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_corrects_a_past_stay_and_leaves_the_others_alone(): void
    {
        Queue::fake();

        [$user, $painting] = $this->painting();
        $hallway = Location::factory()->create(['account_id' => $user->account_id]);
        $past = PossessionLocationStay::factory()->create(['possession_id' => $painting->id, 'arrived_on' => '2019-11-08', 'left_on' => '2024-06-04']);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $painting->id, 'arrived_on' => '2024-06-04']);
        $painting->update(['current_location_id' => $open->location_id]);

        $stay = new UpdatePossessionLocationStay(
            user: $user,
            stay: $past,
            location: $hallway,
            arrivedOn: null,
            leftOn: CarbonImmutable::parse('2024-06-10'),
            reason: ' <i>Pam liked it there</i> ',
        )->execute();

        $this->assertInstanceOf(PossessionLocationStay::class, $stay);
        $this->assertDatabaseHas('possession_location_stays', [
            'id' => $past->id,
            'location_id' => $hallway->id,
            'arrived_on' => null,
            'reason' => 'Pam liked it there',
        ]);
        $this->assertEquals('2024-06-10', $past->refresh()->left_on->toDateString());
        $this->assertEquals('2024-06-04', $open->refresh()->arrived_on->toDateString());
        $this->assertEquals($open->location_id, $painting->refresh()->current_location_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionLocationStayUpdate
                && $job->parameters === ['name' => 'Watercolour of the office'],
        );
    }

    #[Test]
    public function correcting_where_the_open_stay_is_changes_the_current_location(): void
    {
        Queue::fake();

        [$user, $painting] = $this->painting();
        $hallway = Location::factory()->create(['account_id' => $user->account_id]);
        $open = PossessionLocationStay::factory()->create(['possession_id' => $painting->id]);

        new UpdatePossessionLocationStay(
            user: $user,
            stay: $open,
            location: $hallway,
            arrivedOn: CarbonImmutable::parse('2024-06-04'),
        )->execute();

        $this->assertNull($open->refresh()->left_on);
        $this->assertEquals($hallway->id, $painting->refresh()->current_location_id);
    }

    #[Test]
    public function it_refuses_a_past_stay_without_the_day_it_left(): void
    {
        [$user, $painting] = $this->painting();
        $past = PossessionLocationStay::factory()->create(['possession_id' => $painting->id, 'left_on' => '2024-06-04']);

        $this->assertRefused('Enter the day it left.', fn (): PossessionLocationStay => new UpdatePossessionLocationStay(
            user: $user,
            stay: $past,
            location: $past->location,
        )->execute());
    }

    #[Test]
    public function it_refuses_a_day_it_left_before_the_day_it_arrived(): void
    {
        [$user, $painting] = $this->painting();
        $past = PossessionLocationStay::factory()->create(['possession_id' => $painting->id, 'left_on' => '2024-06-04']);

        $this->assertRefused('This is before the day it arrived.', fn (): PossessionLocationStay => new UpdatePossessionLocationStay(
            user: $user,
            stay: $past,
            location: $past->location,
            arrivedOn: CarbonImmutable::parse('2024-06-04'),
            leftOn: CarbonImmutable::parse('2024-06-03'),
        )->execute());
    }

    #[Test]
    public function it_refuses_to_give_the_open_stay_a_day_it_left(): void
    {
        [$user, $painting] = $this->painting();
        $open = PossessionLocationStay::factory()->create(['possession_id' => $painting->id]);

        $this->assertRefused('Move the possession to close the current stay.', fn (): PossessionLocationStay => new UpdatePossessionLocationStay(
            user: $user,
            stay: $open,
            location: $open->location,
            leftOn: CarbonImmutable::parse('2024-06-04'),
        )->execute());
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        [$user, $painting] = $this->painting();
        $open = PossessionLocationStay::factory()->create(['possession_id' => $painting->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionLocationStay(
            user: $user,
            stay: $open,
            location: Location::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_stay_of_another_account(): void
    {
        $user = User::factory()->create();
        $stay = PossessionLocationStay::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionLocationStay(
            user: $user,
            stay: $stay,
            location: Location::factory()->create(['account_id' => $user->account_id]),
        )->execute();
    }

    /** @return array{0: User, 1: Possession} */
    private function painting(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Watercolour of the office'])];
    }

    private function assertRefused(string $message, callable $update): void
    {
        try {
            $update();

            $this->fail('The correction was accepted.');
        } catch (ValidationException $e) {
            $this->assertEquals(['left_on' => [$message]], $e->errors());
        }
    }
}
