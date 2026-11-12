<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateLocation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLocationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_name_the_emoji_and_the_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse', 'emoji' => '📦']);

        new UpdateLocation(
            user: $user,
            location: $warehouse,
            name: 'Annex',
            emoji: null,
            address: '1725 Slough Avenue',
        )->execute();

        $this->assertDatabaseHas('locations', [
            'id' => $warehouse->id,
            'name' => 'Annex',
            'emoji' => null,
            'address' => '1725 Slough Avenue',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LocationUpdate
                && $job->parameters === ['name' => 'Annex'],
        );
    }

    #[Test]
    public function it_moves_a_location_with_everything_inside_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $stamford = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create();
        $shelf = Location::factory()->inside($warehouse)->create();

        new UpdateLocation(user: $user, location: $warehouse, name: $warehouse->name, parent: $stamford)->execute();

        $this->assertSame($stamford->id, $warehouse->refresh()->parent_id);
        $this->assertSame($warehouse->id, $shelf->refresh()->parent_id);
    }

    #[Test]
    public function it_moves_a_location_to_the_top_level(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create();

        new UpdateLocation(user: $user, location: $warehouse, name: $warehouse->name)->execute();

        $this->assertNull($warehouse->refresh()->parent_id);
    }

    #[Test]
    public function it_refuses_to_put_a_location_inside_itself(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateLocation(user: $user, location: $warehouse, name: $warehouse->name, parent: $warehouse)->execute();
    }

    #[Test]
    public function it_refuses_to_put_a_location_inside_one_it_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create();
        $shelf = Location::factory()->inside($warehouse)->create();

        try {
            new UpdateLocation(user: $user, location: $scranton, name: $scranton->name, parent: $shelf)->execute();
            $this->fail('The location was put inside one it holds.');
        } catch (InvalidArgumentException) {
            $this->assertNull($scranton->refresh()->parent_id);
        }
    }

    #[Test]
    public function it_refuses_a_name_that_holds_nothing_but_markup(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);

        try {
            new UpdateLocation(user: $user, location: $warehouse, name: '<b></b>')->execute();
            $this->fail('The location was left without a name.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertSame('Warehouse', $warehouse->refresh()->name);
        }
    }

    #[Test]
    public function it_refuses_a_parent_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        $stamford = Location::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateLocation(user: $user, location: $warehouse, name: $warehouse->name, parent: $stamford)->execute();
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateLocation(user: $user, location: $warehouse, name: 'Annex')->execute();
    }

    #[Test]
    public function it_refuses_a_parent_whose_ancestry_already_loops(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $annex = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        $shelf = Location::factory()->create(['account_id' => $user->account_id]);
        Location::query()->whereKey($annex->id)->update(['parent_id' => $warehouse->id]);
        Location::query()->whereKey($warehouse->id)->update(['parent_id' => $annex->id]);

        try {
            new UpdateLocation(user: $user, location: $shelf, name: $shelf->name, parent: $annex)->execute();
            $this->fail('The location was put under a loop.');
        } catch (InvalidArgumentException) {
            $this->assertNull($shelf->refresh()->parent_id);
        }
    }
}
