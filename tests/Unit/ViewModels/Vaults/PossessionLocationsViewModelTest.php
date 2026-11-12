<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLocationsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLocationsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_no_current_location_and_no_rows_without_a_stay(): void
    {
        [$user, $press] = $this->press();

        $viewModel = new PossessionLocationsViewModel(user: $user, possession: $press);

        $this->assertNull($viewModel->current());
        $this->assertSame([], $viewModel->rows());
        $this->assertSame('', $viewModel->countLine());
        $this->assertSame('It has no current location', $viewModel->form()['subtitle']);
    }

    #[Test]
    public function it_shows_where_it_is_kept_now_with_its_path_and_the_nearest_address(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 10:00'));
        [$user, $press] = $this->press();
        $farm = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'address' => 'Honesdale, Pennsylvania']);
        $barn = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Barn', 'parent_id' => $farm->id]);
        $loft = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Loft', 'parent_id' => $barn->id]);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $loft->id, 'arrived_on' => '2025-03-21']);

        $current = new PossessionLocationsViewModel(user: $user, possession: $press)->current();

        $this->assertSame('Schrute Farms › Barn ›', $current['head']);
        $this->assertSame('Loft', $current['leaf']);
        $this->assertSame('Honesdale, Pennsylvania', $current['address']);
        $this->assertSame('1 year and 7 months', $current['held']);
        $this->assertNull($current['reason']);
    }

    #[Test]
    public function it_lists_the_stays_newest_first_with_their_lengths_and_the_gaps_between_them(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 10:00'));
        [$user, $press] = $this->press();
        $farmhouse = $this->location($user, 'Farmhouse');
        $barn = $this->location($user, 'Barn');
        $workshop = $this->location($user, 'Workshop');
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $farmhouse->id, 'arrived_on' => null, 'left_on' => '2019-10-22']);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id, 'arrived_on' => '2019-11-08', 'left_on' => '2025-03-14']);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $workshop->id, 'arrived_on' => '2025-03-14', 'left_on' => '2025-03-21', 'reason' => 'Appraisal']);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id, 'arrived_on' => '2025-03-21', 'left_on' => null]);

        $viewModel = new PossessionLocationsViewModel(user: $user, possession: $press);
        $rows = $viewModel->rows();

        $this->assertSame(['stay', 'stay', 'stay', 'gap', 'stay'], array_column($rows, 'kind'));
        $this->assertSame(['Barn', 'Workshop', 'Barn', 'Farmhouse'], array_values(array_filter(array_map(fn (array $row): ?string => $row['leaf'] ?? null, $rows))));
        $this->assertTrue($rows[0]['isCurrent']);
        $this->assertFalse($rows[1]['isCurrent']);
        $this->assertSame('7 days', $rows[1]['length']);
        $this->assertSame('Appraisal', $rows[1]['reason']);
        $this->assertSame('5 years and 4 months', $rows[2]['length']);
        $this->assertStringStartsWith('No location recorded from ', $rows[3]['text']);
        $this->assertStringEndsWith(', 17 days.', $rows[3]['text']);
        $this->assertSame('length unknown', $rows[4]['length']);
        $this->assertStringStartsWith('Date unknown to ', $rows[4]['period']);
        $this->assertSame('4 stays in 3 locations', $viewModel->countLine());
    }

    #[Test]
    public function it_warns_differently_before_deleting_the_open_stay_and_a_past_one(): void
    {
        [$user, $press] = $this->press();
        $barn = $this->location($user, 'Barn');
        $cellar = $this->location($user, 'Cellar');
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id, 'arrived_on' => '2019-11-08', 'left_on' => '2024-06-04']);
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $cellar->id, 'arrived_on' => '2024-06-04']);

        $rows = new PossessionLocationsViewModel(user: $user, possession: $press)->rows();

        $this->assertSame('Delete the stay in Cellar?', $rows[0]['deleteTitle']);
        $this->assertStringStartsWith('The possession will have no current location until it is moved, and this cannot be undone. The stay in Barn stays closed on ', $rows[0]['deleteWarning']);
        $this->assertSame('This period will show as unrecorded, and this cannot be undone. Barn itself stays in your locations.', $rows[1]['deleteWarning']);
    }

    #[Test]
    public function it_offers_every_location_of_the_account_as_its_full_path(): void
    {
        [$user, $press] = $this->press();
        $farm = $this->location($user, 'Schrute Farms');
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Barn', 'parent_id' => $farm->id]);
        Location::factory()->create(['name' => 'Dunder Mifflin']);

        $labels = array_column(new PossessionLocationsViewModel(user: $user, possession: $press)->locationOptions(), 'label');

        $this->assertSame(['Schrute Farms', 'Schrute Farms › Barn'], $labels);
    }

    #[Test]
    public function it_starts_a_move_today_from_where_it_is(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 10:00'));
        [$user, $press] = $this->press();
        PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $this->location($user, 'Barn')->id]);

        $viewModel = new PossessionLocationsViewModel(user: $user, possession: $press);

        $this->assertSame('2026-11-12', $viewModel->form()['values']['moved_on']);
        $this->assertSame('From Barn', $viewModel->form()['subtitle']);
        $this->assertSame('The stay in Barn closes that day. A new stay in :new opens the same day.', $viewModel->preview()['closes']);
    }

    #[Test]
    public function it_fills_the_form_with_the_stay_being_corrected(): void
    {
        [$user, $press] = $this->press();
        $barn = $this->location($user, 'Barn');
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'location_id' => $barn->id, 'arrived_on' => null, 'left_on' => '2024-06-04', 'reason' => 'After the harvest']);

        $form = new PossessionLocationsViewModel(user: $user, possession: $press, editing: $stay)->form();

        $this->assertTrue($form['isEditing']);
        $this->assertFalse($form['isOpenStay']);
        $this->assertSame('A past stay', $form['subtitle']);
        $this->assertSame(['location_id' => $barn->uuid, 'arrived_on' => '', 'left_on' => '2024-06-04', 'reason' => 'After the harvest', 'note' => ''], $form['values']);
    }

    /** @return array{0: User, 1: Possession} */
    private function press(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press'])];
    }

    private function location(User $user, string $name): Location
    {
        return Location::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
    }
}
