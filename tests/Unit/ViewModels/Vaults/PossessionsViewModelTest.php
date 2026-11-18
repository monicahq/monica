<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionStatusEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionLoan;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_empty_while_the_vault_holds_no_possession(): void
    {
        [$user, $vault] = $this->scranton();
        Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertTrue($viewModel->isEmpty());
    }

    #[Test]
    public function it_is_not_empty_with_a_possession_sold_long_ago(): void
    {
        [$user, $vault] = $this->scranton();
        Possession::factory()->create(['vault_id' => $vault->id, 'status' => PossessionStatusEnum::Sold]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertFalse($viewModel->isEmpty());
    }

    #[Test]
    public function it_offers_the_first_eight_types_of_the_account_by_name(): void
    {
        [$user, $vault] = $this->scranton();

        foreach (['Paper', 'Vehicle', 'Books', 'Wine', 'Tools', 'Watches', 'Art', 'Coins', 'Stamps'] as $name) {
            PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
        }

        PossessionType::factory()->create(['name' => 'Beets']);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertSame(['Art', 'Books', 'Coins', 'Paper', 'Stamps', 'Tools', 'Vehicle', 'Watches'], array_column($viewModel->types(), 'name'));
    }

    #[Test]
    public function it_names_the_first_two_fields_of_a_type_in_the_order_of_the_form(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle', 'icon' => 'car']);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Purchase']);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Mileage', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Dealer', 'position' => 2]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Registration', 'position' => 1]);

        $type = new PossessionsViewModel(user: $user, vault: $vault)->types()[0];

        $this->assertSame('Registration, Dealer', $type['fields']);
        $this->assertSame('car', $type['icon']);
        $this->assertSame(route('possessions.new', ['vault' => $vault, 'possessionType' => $vehicle]), $type['newUrl']);
    }

    #[Test]
    public function it_says_a_type_has_no_field_and_draws_it_with_the_stand_in_glyph(): void
    {
        [$user, $vault] = $this->scranton();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Other', 'icon' => null]);

        $type = new PossessionsViewModel(user: $user, vault: $vault)->types()[0];

        $this->assertSame(Section::DEFAULT_ICON, $type['icon']);
    }

    #[Test]
    public function it_says_nothing_about_locations_when_the_account_has_none(): void
    {
        [$user, $vault] = $this->scranton();
        Location::factory()->create(['name' => 'Schrute Farms']);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertSame('', $viewModel->locationsLine());
        $this->assertFalse($viewModel->hasLocations());
    }

    #[Test]
    public function it_names_three_places_and_counts_the_ones_inside_them(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        Location::factory()->inside($office)->create(['name' => 'Reception']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Annex']);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertTrue($viewModel->hasLocations());
    }

    /** @return array{0: User, 1: Vault} */
    #[Test]
    public function it_lists_the_featured_possessions_in_their_order_and_the_others_by_name(): void
    {
        [$user, $vault] = $this->scranton();
        Possession::factory()->featured(2)->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring', 'description' => 'Drove it into the lake']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Paper shredder']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet cart']);
        Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Stapler in jello']);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertSame(['Chrysler Sebring', 'Dundie award'], array_column($viewModel->featured(), 'name'));
        $this->assertSame('Drove it into the lake', $viewModel->featured()[0]['description']);
        $this->assertSame(['Beet cart', 'Paper shredder'], array_column($viewModel->others(), 'name'));
    }

    #[Test]
    public function it_describes_each_possession_with_its_type_place_value_and_whether_it_is_lent(): void
    {
        app()->setLocale('en');
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle', 'icon' => 'car', 'colour' => '#3f5fd0']);
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        $car = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $vehicle->id, 'name' => 'Chrysler Sebring', 'current_location_id' => $annex->id, 'declared_value' => '18400', 'declared_value_currency' => 'eur']);
        $award = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $vehicle->id, 'name' => 'Dundie award']);
        $pot = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $vehicle->id, 'name' => 'Teapot']);
        PossessionLoan::factory()->active()->create(['possession_id' => $car->id, 'direction' => PossessionLoanDirectionEnum::Lent]);
        PossessionLoan::factory()->returned()->create(['possession_id' => $award->id, 'direction' => PossessionLoanDirectionEnum::Lent]);
        PossessionLoan::factory()->active()->create(['possession_id' => $pot->id, 'direction' => PossessionLoanDirectionEnum::Borrowed]);

        [$sebring, $dundie, $teapot] = new PossessionsViewModel(user: $user, vault: $vault)->others();

        $this->assertSame('Vehicle', $sebring['type']);
        $this->assertSame('car', $sebring['icon']);
        $this->assertSame('Office › Annex', $sebring['place']);
        $this->assertSame('€18,400', $sebring['value']);
        $this->assertTrue($sebring['isLent']);
        $this->assertSame('—', $dundie['value']);
        $this->assertFalse($dundie['isLent']);
        $this->assertFalse($teapot['isLent']);
    }

    #[Test]
    public function it_narrows_everything_to_a_location_and_the_locations_inside_it(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office', 'address' => '1725 Slough Avenue, Scranton']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Paper shredder', 'current_location_id' => $office->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet cart', 'current_location_id' => $annex->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Forklift', 'current_location_id' => $warehouse->id]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault, location: $office);

        $this->assertSame(['Beet cart', 'Paper shredder'], array_column($viewModel->others(), 'name'));
        $this->assertSame('Annex', $viewModel->others()[0]['place']);
        $this->assertSame('Office', $viewModel->selection()['name']);
        $this->assertSame('1725 Slough Avenue, Scranton', $viewModel->selection()['address']);
        $this->assertSame(2, $viewModel->selection()['count']);
        $this->assertStringContainsString('Office', $viewModel->othersTitle());
    }

    #[Test]
    public function it_splits_a_location_between_the_locations_inside_it_and_its_own_spot(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        $kitchen = Location::factory()->inside($office)->create(['name' => 'Kitchen']);
        Location::factory()->inside($office)->create(['name' => 'Conference room']);
        Possession::factory()->count(2)->create(['vault_id' => $vault->id, 'current_location_id' => $annex->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $kitchen->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault, location: $office);

        $this->assertSame(['Annex', 'Kitchen'], array_slice(array_column($viewModel->parts(), 'name'), 0, 2));
        $this->assertSame([2, 1, 1], array_column($viewModel->parts(), 'count'));
    }

    #[Test]
    public function it_splits_every_possession_between_the_top_level_locations_and_none(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);
        Possession::factory()->create(['vault_id' => $vault->id]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertSame('Office', $viewModel->parts()[0]['name']);
    }

    #[Test]
    public function it_draws_no_split_below_two_parts(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Office']);
        Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);

        $viewModel = new PossessionsViewModel(user: $user, vault: $vault);

        $this->assertSame([], $viewModel->parts());
        $this->assertSame('', $viewModel->partsLabel());
    }

    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
