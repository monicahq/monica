<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionValuationViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionValuationViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_summarises_a_possession_without_valuations(): void
    {
        [$user, $dundie] = $this->dundie();

        $viewModel = new PossessionValuationViewModel(user: $user, possession: $dundie);
        $summary = $viewModel->summary();

        $this->assertSame('—', $summary['current']);
        $this->assertSame('No valuation yet', $summary['currentNote']);
        $this->assertSame('—', $summary['change']);
        $this->assertSame('Not insured', $summary['insured']);
        $this->assertFalse($summary['isUnderInsured']);
        $this->assertSame([], $viewModel->valuations());
    }

    #[Test]
    public function it_reads_the_current_value_and_how_it_moved_from_the_previous(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'amount' => '500.00']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14', 'amount' => '650.00', 'type' => PossessionValuationTypeEnum::Appraisal]);

        $summary = new PossessionValuationViewModel(user: $user, possession: $dundie)->summary();

        $this->assertSame('$650', $summary['current']);
        $this->assertStringStartsWith('Appraisal, ', $summary['currentNote']);
        $this->assertSame('Up $150', $summary['change']);
        $this->assertStringStartsWith('From $500, ', $summary['changeNote']);
    }

    #[Test]
    public function it_compares_nothing_across_currencies(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'currency' => 'gbp']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14']);

        $viewModel = new PossessionValuationViewModel(user: $user, possession: $dundie);

        $this->assertSame('—', $viewModel->summary()['change']);
        $this->assertSame('Different currencies', $viewModel->summary()['changeNote']);
        $this->assertSame('In USD', $viewModel->valuations()[0]['change']);
        $this->assertSame('First valuation', $viewModel->valuations()[1]['change']);
    }

    #[Test]
    public function it_says_how_far_the_insurance_falls_below_the_current_value(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance', 'insured_value' => '500.00']);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'amount' => '650.00']);

        $summary = new PossessionValuationViewModel(user: $user, possession: $dundie)->summary();

        $this->assertSame('$500', $summary['insured']);
        $this->assertSame('$150 below the current value', $summary['insuredNote']);
        $this->assertTrue($summary['isUnderInsured']);
    }

    #[Test]
    public function it_lists_the_newest_first_with_what_was_recorded(): void
    {
        [$user, $dundie] = $this->dundie();
        $older = PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'amount' => '700.00']);
        $newer = PossessionValuation::factory()->create([
            'possession_id' => $dundie->id,
            'valued_on' => '2026-03-14',
            'amount' => '650.00',
            'confidence' => PossessionValuationConfidenceEnum::Certain,
            'valuer' => 'Scranton Jewelers',
            'reference_number' => 'APP-2026-0212',
        ]);
        $newer->documents()->attach(File::factory()->create(['account_id' => $user->account_id, 'original_name' => 'appraisal.pdf']));

        $valuations = new PossessionValuationViewModel(user: $user, possession: $dundie)->valuations();

        $this->assertSame([$newer->uuid, $older->uuid], array_column($valuations, 'id'));
        $this->assertTrue($valuations[0]['isCurrent']);
        $this->assertFalse($valuations[1]['isCurrent']);
        $this->assertSame('Down $50 from the previous', $valuations[0]['change']);
        $this->assertSame('Scranton Jewelers', $valuations[0]['valuer']);
        $this->assertSame(['Certain', 'Not recorded', 'APP-2026-0212', 'Not recorded'], array_column($valuations[0]['parts'], 'value'));
        $this->assertSame('Not recorded', $valuations[1]['parts'][0]['value']);
        $this->assertSame(['appraisal.pdf'], array_column($valuations[0]['documents'], 'name'));
    }

    #[Test]
    public function it_says_what_deleting_a_valuation_leaves(): void
    {
        [$user, $dundie] = $this->dundie();
        $older = PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2019-11-20', 'amount' => '500.00']);
        $newer = PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14', 'amount' => '650.00']);
        $newer->documents()->attach(File::factory()->create(['account_id' => $user->account_id]));

        $viewModel = new PossessionValuationViewModel(user: $user, possession: $dundie);

        $this->assertStringStartsWith('The current value goes back to $500, from the own estimate of ', $viewModel->deleteWarning($newer));
        $this->assertStringEndsWith('1 attached document goes with it. This cannot be undone.', $viewModel->deleteWarning($newer));
        $this->assertSame('The current value stays $650. To correct a figure, record a new valuation instead. This cannot be undone.', $viewModel->deleteWarning($older));
    }

    #[Test]
    public function it_says_the_possession_has_no_valuation_left_after_deleting_the_only_one(): void
    {
        [$user, $dundie] = $this->dundie();
        $only = PossessionValuation::factory()->create(['possession_id' => $dundie->id]);

        $viewModel = new PossessionValuationViewModel(user: $user, possession: $dundie);

        $this->assertStringStartsWith('The possession will no longer have a valuation.', $viewModel->deleteWarning($only));
    }

    #[Test]
    public function the_preview_compares_against_the_current_valuation(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'valued_on' => '2026-03-14', 'amount' => '650.00']);

        $preview = new PossessionValuationViewModel(user: $user, possession: $dundie)->preview();

        $this->assertSame('2026-03-14', $preview['date']);
        $this->assertSame('650.00', $preview['amount']);
        $this->assertSame('usd', $preview['currency']);
        $this->assertSame('This becomes the current value, up :difference from $650.', $preview['up']);
    }

    #[Test]
    public function a_new_valuation_starts_in_the_currency_of_the_declared_value_until_one_is_recorded(): void
    {
        [$user, $dundie] = $this->dundie();

        $this->assertSame('usd', new PossessionValuationViewModel(user: $user, possession: $dundie)->preselectedCurrency()?->value);

        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'currency' => 'gbp']);

        $this->assertSame('gbp', new PossessionValuationViewModel(user: $user, possession: $dundie->fresh())->preselectedCurrency()?->value);
    }

    /** @return array{0: User, 1: Possession} */
    private function dundie(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, Possession::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Dundie award',
            'declared_value' => '600.00',
            'declared_value_currency' => 'usd',
        ])];
    }
}
