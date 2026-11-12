<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLocationStayControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_locations_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Barn']);
        $press = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $barn->id]);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id, 'reason' => 'After the harvest']);

        $this->actingAs($user)->get(route('possessions.locationStays.index', [$vault, $press]))
            ->assertOk()
            ->assertSee('Kept now in')
            ->assertSee('After the harvest')
            ->assertSee('Move elsewhere')
            ->assertSee('aria-current="page"', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_moves_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $cellar = Location::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => null]);

        $this->actingAs($user)->post(route('possessions.locationStays.create', [$vault, $press]), [
            'location_id' => $cellar->uuid,
            'moved_on' => '2024-06-04',
            'reason' => 'Harvest season',
        ])
            ->assertRedirect(route('possessions.locationStays.index', [$vault, $press]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_location_stays', [
            'possession_id' => $press->id,
            'location_id' => $cellar->id,
            'left_on' => null,
            'reason' => 'Harvest season',
        ]);
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.locationStays.create', [$vault, $press]), [
            'location_id' => Location::factory()->create()->uuid,
            'moved_on' => '2024-06-04',
        ])->assertSessionHasErrors('location_id');

        $this->assertDatabaseCount('possession_location_stays', 0);
    }

    #[Test]
    public function it_returns_the_form_when_the_move_is_refused(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $barn = Location::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $barn->id]);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id]);

        $this->actingAs($user)->post(route('possessions.locationStays.create', [$vault, $press]), [
            'location_id' => $barn->uuid,
            'moved_on' => '2024-06-04',
        ])->assertSessionHasErrors('location_id');
    }

    #[Test]
    public function it_shows_a_stay_in_the_form_for_correcting(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'left_on' => '2024-06-04', 'reason' => 'After the harvest']);

        $this->actingAs($user)->get(route('possessions.locationStays.edit', [$vault, $press, $stay]))
            ->assertOk()
            ->assertSee('Correct this stay')
            ->assertSee('value="After the harvest"', escape: false)
            ->assertSee('name="left_on"', escape: false)
            ->assertSee(route('possessions.locationStays.update', [$vault, $press, $stay]));
    }

    #[Test]
    public function it_corrects_a_stay(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $loft = Location::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'left_on' => '2024-06-04']);

        $this->actingAs($user)->put(route('possessions.locationStays.update', [$vault, $press, $stay]), [
            'location_id' => $loft->uuid,
            'arrived_on' => '',
            'left_on' => '2024-06-05',
            'note' => 'Mose found the receipt.',
        ])
            ->assertRedirect(route('possessions.locationStays.index', [$vault, $press]))
            ->assertSessionHas('status');

        $stay->refresh();
        $this->assertEquals($loft->id, $stay->location_id);
        $this->assertNull($stay->arrived_on);
        $this->assertEquals('2024-06-05', $stay->left_on->toDateString());
        $this->assertEquals('Mose found the receipt.', $stay->note);
    }

    #[Test]
    public function it_deletes_a_stay(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id]);

        $this->actingAs($user)->delete(route('possessions.locationStays.destroy', [$vault, $press, $stay]))
            ->assertRedirect(route('possessions.locationStays.index', [$vault, $press]))
            ->assertSessionHas('status');

        $this->assertModelMissing($stay);
    }

    #[Test]
    public function it_answers_not_found_for_a_stay_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        $this->actingAs($user)->get(route('possessions.locationStays.edit', [$vault, $press, $stay]))
            ->assertNotFound();
        $this->actingAs($user)->delete(route('possessions.locationStays.destroy', [$vault, $press, $stay]))
            ->assertNotFound();

        $this->assertModelExists($stay);
    }
}
