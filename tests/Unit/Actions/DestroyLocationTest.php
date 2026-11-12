<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyLocation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_empty_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shelf = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Shelf 3']);

        new DestroyLocation(user: $user, location: $shelf)->execute();

        $this->assertModelMissing($shelf);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationDeletion
                && $job->parameters === ['name' => 'Shelf 3'],
        );
    }

    #[Test]
    public function it_refuses_a_location_holding_another(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        Location::factory()->inside($warehouse)->create();

        try {
            new DestroyLocation(user: $user, location: $warehouse)->execute();
            $this->fail('A location holding another was deleted.');
        } catch (AuthorizationException) {
            $this->assertModelExists($warehouse);
        }
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shelf = Location::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyLocation(user: $user, location: $shelf)->execute();
    }

    #[Test]
    public function it_counts_what_is_inside_afresh_rather_than_from_what_was_loaded(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse->setRelation('children', new Collection);
        Location::factory()->inside($warehouse)->create();

        try {
            new DestroyLocation(user: $user, location: $warehouse)->execute();
            $this->fail('A location holding another was deleted.');
        } catch (AuthorizationException) {
            $this->assertModelExists($warehouse);
        }
    }

    #[Test]
    public function it_keeps_the_possessions_that_sat_there_without_their_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $parkingLot = Location::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'current_location_id' => $parkingLot->id,
        ]);

        new DestroyLocation(user: $user, location: $parkingLot)->execute();

        $this->assertModelExists($sebring);
        $this->assertNull($sebring->refresh()->current_location_id);
    }
}
