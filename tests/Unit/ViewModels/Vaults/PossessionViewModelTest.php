<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Enums\VaultColourEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionFieldValue;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionType;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_places_a_nested_possession_at_its_top_level_location(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $annex = Location::factory()->inside($office)->create(['name' => 'Annex']);
        $shelf = Location::factory()->inside($annex)->create(['name' => 'Top shelf']);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $shelf->id]);

        $header = new PossessionViewModel(user: $user, possession: $dundie)->header();

        $this->assertSame(['name' => 'Top shelf', 'holder' => 'Dunder Mifflin'], $header['place']);
    }

    #[Test]
    public function it_places_a_possession_at_a_top_level_location_without_a_holder(): void
    {
        [$user, $vault] = $this->scranton();
        $office = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);

        $this->assertSame(
            ['name' => 'Dunder Mifflin', 'holder' => null],
            new PossessionViewModel(user: $user, possession: $dundie)->header()['place'],
        );
    }

    #[Test]
    public function it_says_when_a_possession_has_no_location(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $viewModel = new PossessionViewModel(user: $user, possession: $dundie);

        $this->assertNull($viewModel->header()['place']);
    }

    #[Test]
    public function it_lists_the_filled_fields_by_group_in_the_order_of_the_type(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Purchase', 'position' => 2]);
        $engine = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Engine', 'position' => 1]);
        $empty = PossessionFieldGroup::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Insurance', 'position' => 3]);
        $dealer = PossessionField::factory()->inGroup($purchase)->create(['name' => 'Dealer', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Price paid', 'position' => 2]);
        $fuel = PossessionField::factory()->inGroup($engine)->create(['name' => 'Fuel', 'position' => 1]);
        PossessionField::factory()->inGroup($empty)->create(['name' => 'Policy']);
        $plate = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Plate']);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $vehicle->id]);
        $this->fill($sebring, $dealer, 'Scranton Chrysler');
        $this->fill($sebring, $fuel, 'Petrol');
        $this->fill($sebring, $plate, 'PA-1234');

        $groups = new PossessionViewModel(user: $user, possession: $sebring)->groups();

        $this->assertSame([
            ['title' => 'Engine', 'fields' => [['label' => 'Fuel', 'value' => 'Petrol']]],
            ['title' => 'Purchase', 'fields' => [['label' => 'Dealer', 'value' => 'Scranton Chrysler']]],
            ['title' => null, 'fields' => [['label' => 'Plate', 'value' => 'PA-1234']]],
        ], $groups);
    }

    #[Test]
    public function its_value_comes_from_the_latest_valuation_with_where_it_comes_from_and_how_it_moved(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'declared_value' => '600.00', 'declared_value_currency' => 'usd']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'amount' => '500.00']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14', 'amount' => '650.00', 'type' => PossessionValuationTypeEnum::Appraisal, 'valuer' => 'Scranton Jewelers']);

        $viewModel = new PossessionViewModel(user: $user, possession: $dundie);

        $this->assertSame('$650', $viewModel->value());
        $this->assertStringStartsWith('Appraisal by Scranton Jewelers, ', $viewModel->valuation()['source']);
        $this->assertSame('Up $150 from the previous estimate', $viewModel->valuation()['change']);
    }

    #[Test]
    public function without_a_valuation_its_value_is_the_declared_one_with_nothing_said_about_its_source(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'declared_value' => '600.00', 'declared_value_currency' => 'usd']);

        $viewModel = new PossessionViewModel(user: $user, possession: $dundie);

        $this->assertSame('$600', $viewModel->value());
        $this->assertNull($viewModel->valuation());
    }

    #[Test]
    public function it_gives_no_value_when_none_is_declared(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->assertNull(new PossessionViewModel(user: $user, possession: $dundie)->value());
    }

    #[Test]
    public function it_reads_the_acquisition_from_its_transaction_and_gives_a_quantity_above_one(): void
    {
        [$user, $vault] = $this->scranton();
        $dundies = Possession::factory()->create(['vault_id' => $vault->id, 'quantity' => 3]);
        PossessionTransaction::factory()->create([
            'possession_id' => $dundies->id,
            'type' => PossessionTransactionTypeEnum::GiftReceived,
            'occurred_on' => '2005-03-24',
            'counterparty' => 'Michael Scott',
        ]);

        $facts = new PossessionViewModel(user: $user, possession: $dundies)->facts();
        $values = array_column($facts, 'value');

        $this->assertContains('Michael Scott', $values);
        $this->assertContains('3', $values);
    }

    #[Test]
    public function it_leaves_out_what_is_not_known(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'type' => PossessionTransactionTypeEnum::Fee]);

        $labels = array_column(new PossessionViewModel(user: $user, possession: $dundie)->facts(), 'label');

        $this->assertCount(3, $labels);
    }

    #[Test]
    public function it_lists_ten_sections_with_the_overview_the_transactions_the_insurance_the_valuations_the_provenance_the_maintenance_the_loans_and_the_locations_available(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTransaction::factory()->count(2)->create(['possession_id' => $dundie->id]);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);
        PossessionMaintenanceRecord::factory()->count(3)->create(['possession_id' => $dundie->id]);
        PossessionValuation::factory()->count(2)->create(['possession_id' => $dundie->id]);
        PossessionProvenanceEvent::factory()->count(4)->create(['possession_id' => $dundie->id]);
        PossessionLocationStay::factory()->count(5)->create(['possession_id' => $dundie->id]);
        PossessionLoan::factory()->count(2)->returned()->create(['possession_id' => $dundie->id]);

        $sections = new PossessionViewModel(user: $user, possession: $dundie)->sections('transactions');
        $byKey = array_column($sections, null, 'key');

        $this->assertCount(10, $sections);
        $this->assertCount(8, array_filter($sections, fn (array $section): bool => $section['url'] !== null));
        $this->assertSame(2, $byKey['transactions']['count']);
        $this->assertSame(1, $byKey['insurance']['count']);
        $this->assertSame(3, $byKey['maintenance']['count']);
        $this->assertSame(2, $byKey['valuations']['count']);
        $this->assertSame(4, $byKey['provenance']['count']);
        $this->assertSame(5, $byKey['locations']['count']);
        $this->assertSame(2, $byKey['loans']['count']);
        $this->assertTrue($byKey['transactions']['isCurrent']);
        $this->assertFalse($byKey['overview']['isCurrent']);
    }

    #[Test]
    public function it_says_who_insures_the_possession_and_for_how_much(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'status' => PossessionInsuranceStatusEnum::Expired, 'insurer' => 'Utica Assurance']);

        $labels = array_column(new PossessionViewModel(user: $user, possession: $dundie)->facts(), 'label');

        $this->assertNotContains('Insured', $labels);

        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance', 'insured_value' => '500.00', 'currency' => CurrencyEnum::UsDollar]);

        $facts = array_column(new PossessionViewModel(user: $user, possession: $dundie->fresh())->facts(), 'value', 'label');

        $this->assertSame('Sabre Insurance, $500', $facts['Insured']);
    }

    #[Test]
    public function it_gives_the_tags_of_the_possession_by_name_with_their_colour(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $insured = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured', 'colour' => VaultColourEnum::Teal->value]);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);
        $dundie->tags()->attach([$insured->id, $fragile->id]);

        $tags = new PossessionViewModel(user: $user, possession: $dundie)->header()['tags'];

        $this->assertSame(['Fragile', 'Insured'], array_column($tags, 'name'));
        $this->assertSame(VaultColourEnum::Teal->value, $tags[1]['colour']);
        $this->assertSame($insured->uuid, $tags[1]['id']);
    }

    #[Test]
    public function it_offers_every_tag_of_the_account_saying_how_many_other_possessions_carry_it(): void
    {
        [$user, $vault] = $this->scranton();
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $sebring = Possession::factory()->create(['vault_id' => $otherVault->id]);
        $insured = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);
        $toSell = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell']);
        PossessionTag::factory()->create(['name' => 'Heirloom']);
        $dundie->tags()->attach([$insured->id, $fragile->id]);
        $mug->tags()->attach($fragile->id);
        $sebring->tags()->attach([$insured->id, $fragile->id]);

        $options = new PossessionViewModel(user: $user, possession: $dundie)->tagOptions();

        $this->assertSame(['Fragile', 'Insured', 'To sell'], array_column($options, 'name'));
        $this->assertSame([true, true, false], array_column($options, 'carried'));
        $this->assertSame(['2 others', '1 other', 'Unused'], array_column($options, 'others'));
        $this->assertSame($toSell->uuid, $options[2]['id']);
    }

    #[Test]
    public function it_counts_the_tags_on_the_possession(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->assertSame('No tags on this possession yet', new PossessionViewModel(user: $user, possession: $dundie)->tagCount());

        $dundie->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id])->id);

        $this->assertSame('1 tag on this possession', new PossessionViewModel(user: $user, possession: $dundie->fresh())->tagCount());

        $dundie->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile'])->id);

        $this->assertSame('2 tags on this possession', new PossessionViewModel(user: $user, possession: $dundie->fresh())->tagCount());
    }

    #[Test]
    public function it_names_the_colour_a_tag_created_from_the_page_will_take(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'colour' => VaultColourEnum::Blue->value]);

        $this->assertSame(
            ['colour' => VaultColourEnum::Green->value, 'name' => 'Green'],
            new PossessionViewModel(user: $user, possession: $dundie)->nextTagColour(),
        );
    }

    private function fill(Possession $possession, PossessionField $field, string $value): void
    {
        PossessionFieldValue::factory()->create([
            'possession_id' => $possession->id,
            'possession_field_id' => $field->id,
            'value' => $value,
        ]);
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
