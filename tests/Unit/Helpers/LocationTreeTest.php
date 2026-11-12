<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\LocationTree;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationTreeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_orders_each_location_before_the_ones_inside_it_sorted_by_name(): void
    {
        $user = User::factory()->create();
        $stamford = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Stamford']);
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Location::factory()->inside($stamford)->create(['name' => 'Annex']);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->inside($scranton)->create(['name' => 'Annex']);
        Location::factory()->inside($warehouse)->create(['name' => 'Shelf 3']);
        Location::factory()->create(['name' => 'Nashua']);

        $tree = LocationTree::forAccount($user->account_id);

        $this->assertSame(
            ['Scranton', 'Scranton › Annex', 'Scranton › Warehouse', 'Scranton › Warehouse › Shelf 3', 'Stamford', 'Stamford › Annex'],
            $tree->ordered()->map(fn (Location $location): string => $tree->path($location))->all(),
        );
    }

    #[Test]
    public function it_finds_the_address_a_location_falls_back_on(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'address' => '1725 Slough Avenue']);
        $warehouse = Location::factory()->inside($scranton)->create();
        $shelf = Location::factory()->inside($warehouse)->create();
        $farms = Location::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->inside($farms)->create();

        $tree = LocationTree::forAccount($user->account_id);

        $this->assertSame($scranton->id, $tree->addressHolder($scranton)?->id);
        $this->assertSame($scranton->id, $tree->addressHolder($shelf)?->id);
        $this->assertNull($tree->addressHolder($barn));
    }

    #[Test]
    public function it_counts_the_locations_directly_inside_and_lists_every_one_below(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create();
        $annex = Location::factory()->inside($scranton)->create();
        $shelf = Location::factory()->inside($warehouse)->create();

        $tree = LocationTree::forAccount($user->account_id);

        $this->assertSame(2, $tree->childCount($scranton));
        $this->assertSame(0, $tree->childCount($shelf));
        $this->assertEqualsCanonicalizing([$warehouse->id, $annex->id, $shelf->id], $tree->descendantIds($scranton));
    }

    #[Test]
    public function it_stops_walking_a_loop_the_table_holds(): void
    {
        $user = User::factory()->create();
        $annex = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Annex', 'address' => null]);
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse', 'address' => null]);
        Location::query()->whereKey($annex->id)->update(['parent_id' => $warehouse->id]);
        Location::query()->whereKey($warehouse->id)->update(['parent_id' => $annex->id]);

        $tree = LocationTree::forAccount($user->account_id);

        $this->assertSame('Warehouse › Annex', $tree->path($annex->refresh()));
        $this->assertNull($tree->addressHolder($annex));
    }
}
