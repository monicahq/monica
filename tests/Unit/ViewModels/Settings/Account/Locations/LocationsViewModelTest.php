<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Locations;

use App\Models\Location;
use App\Models\User;
use App\ViewModels\Settings\Account\Locations\LocationsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_locations_of_the_account_as_a_tree_sorted_by_name(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Nashua']);
        Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->inside($scranton)->create(['name' => 'Annex']);
        Location::factory()->create(['name' => 'Stamford']);

        $tree = new LocationsViewModel(user: $user)->tree();

        $this->assertSame(['Nashua', 'Scranton'], array_column($tree, 'name'));
        $this->assertSame(['Annex', 'Warehouse'], array_column($tree[1]['children'], 'name'));
        $this->assertSame(2, $tree[1]['children'][0]['level']);
    }

    #[Test]
    public function it_names_the_locations_offered_as_a_parent_by_their_path(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        $nashua = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Nashua']);
        Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->inside($nashua)->create(['name' => 'Warehouse']);

        $options = new LocationsViewModel(user: $user, creating: true)->parentOptions();

        $this->assertSame(
            ['Nashua', 'Nashua › Warehouse', 'Scranton', 'Scranton › Warehouse'],
            array_column($options, 'label'),
        );
    }

    #[Test]
    public function it_leaves_the_edited_location_and_everything_inside_it_out_of_the_parents(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->inside($warehouse)->create(['name' => 'Shelf 3']);
        Location::factory()->inside($scranton)->create(['name' => 'Annex']);

        $options = new LocationsViewModel(user: $user, editing: $warehouse)->parentOptions();

        $this->assertSame(['Scranton', 'Scranton › Annex'], array_column($options, 'label'));
    }

    #[Test]
    public function it_says_why_a_location_holding_another_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        Location::factory()->inside($scranton)->create();

        $tree = new LocationsViewModel(user: $user)->tree();

        $this->assertFalse($tree[0]['mayBeDeleted']);
        $this->assertTrue($tree[0]['children'][0]['mayBeDeleted']);
    }

    #[Test]
    public function it_titles_the_form_after_what_it_does(): void
    {
        $user = User::factory()->create();
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);

        $this->assertFalse(new LocationsViewModel(user: $user)->formIsOpen());
    }

    #[Test]
    public function it_says_when_the_account_has_no_location(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(new LocationsViewModel(user: $user)->isEmpty());
    }
}
