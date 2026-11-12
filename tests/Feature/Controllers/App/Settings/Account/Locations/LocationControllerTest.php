<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Locations;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_locations_of_the_account(): void
    {
        $user = User::factory()->create();
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton']);

        $this->actingAs($user)->get(route('settings.locations.index'))
            ->assertStatus(200)
            ->assertSee('Dunder Mifflin Scranton');
    }

    #[Test]
    public function it_opens_the_form_for_a_new_location(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.locations.new'))
            ->assertStatus(200)
            ->assertSee('action="'.route('settings.locations.create').'"', false);
    }

    #[Test]
    public function it_opens_the_form_for_a_location_inside_another(): void
    {
        $user = User::factory()->create();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton']);

        $this->actingAs($user)->get(route('settings.locations.children.new', $office))
            ->assertStatus(200);
    }

    #[Test]
    public function it_adds_a_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $office = Location::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.locations.create'), [
            'name' => 'Warehouse',
            'parent' => $office->uuid,
            'emoji' => '📦',
            'address' => '',
        ])
            ->assertRedirect(route('settings.locations.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('locations', [
            'account_id' => $user->account_id,
            'parent_id' => $office->id,
            'name' => 'Warehouse',
            'emoji' => '📦',
        ]);
    }

    #[Test]
    public function it_refuses_a_location_without_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.locations.create'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_refuses_a_parent_from_another_account(): void
    {
        $user = User::factory()->create();
        $stamford = Location::factory()->create();

        $this->actingAs($user)->post(route('settings.locations.create'), [
            'name' => 'Annex',
            'parent' => $stamford->uuid,
        ])
            ->assertSessionHasErrors('parent');
    }

    #[Test]
    public function it_refuses_something_that_is_not_one_emoji(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.locations.create'), [
            'name' => 'Schrute Farms',
            'emoji' => 'ab',
        ])
            ->assertSessionHasErrors('emoji');
    }

    #[Test]
    public function it_opens_the_form_to_edit_a_location(): void
    {
        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);

        $this->actingAs($user)->get(route('settings.locations.edit', $warehouse))
            ->assertStatus(200)
            ->assertSee('value="Warehouse"', false);
    }

    #[Test]
    public function it_updates_a_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);

        $this->actingAs($user)->put(route('settings.locations.update', $warehouse), [
            'name' => 'Annex',
        ])
            ->assertRedirect(route('settings.locations.index'))
            ->assertSessionHas('status');

        $this->assertSame('Annex', $warehouse->refresh()->name);
    }

    #[Test]
    public function it_tells_the_form_a_location_cannot_sit_inside_one_it_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        $shelf = Location::factory()->inside($warehouse)->create();

        $this->actingAs($user)
            ->from(route('settings.locations.edit', $warehouse))
            ->put(route('settings.locations.update', $warehouse), [
                'name' => $warehouse->name,
                'parent' => $shelf->uuid,
            ])
            ->assertRedirect(route('settings.locations.edit', $warehouse))
            ->assertSessionHasErrors('parent');

        $this->assertNull($warehouse->refresh()->parent_id);
    }

    #[Test]
    public function it_deletes_a_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shelf = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Shelf 3']);

        $this->actingAs($user)->delete(route('settings.locations.destroy', $shelf))
            ->assertRedirect(route('settings.locations.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($shelf);
    }

    #[Test]
    public function it_says_why_a_location_holding_another_is_kept(): void
    {
        $user = User::factory()->create();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);
        Location::factory()->inside($warehouse)->create();

        $this->actingAs($user)->delete(route('settings.locations.destroy', $warehouse))
            ->assertRedirect(route('settings.locations.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($warehouse);
    }
}
