<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionInsuranceViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionInsuranceViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_the_possession_is_not_insured_without_an_active_record(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'status' => PossessionInsuranceStatusEnum::Pending]);

        $viewModel = new PossessionInsuranceViewModel(user: $user, possession: $dundie);
        $summary = $viewModel->summary();

        $this->assertSame('Not insured', $summary['insured']);
        $this->assertSame('$600', $summary['worth']);
        $this->assertFalse($summary['isUnderInsured']);
        $this->assertSame('—', $summary['renewal']);
        $this->assertNull($viewModel->underInsuranceWarning());
    }

    #[Test]
    public function it_warns_when_the_possession_is_insured_for_less_than_it_is_worth(): void
    {
        $this->travelTo('2026-10-27');

        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create([
            'possession_id' => $dundie->id,
            'insurer' => 'Sabre Insurance',
            'insured_value' => '500.00',
            'is_listed_individually' => true,
            'ends_on' => '2027-01-01',
        ]);

        $viewModel = new PossessionInsuranceViewModel(user: $user, possession: $dundie);
        $summary = $viewModel->summary();

        $this->assertSame('$500', $summary['insured']);
        $this->assertSame('Sabre Insurance, listed individually', $summary['insuredNote']);
        $this->assertSame('$100 more than it is insured for', $summary['worthNote']);
        $this->assertTrue($summary['isUnderInsured']);
        $this->assertSame('In 3 months, with Sabre Insurance', $summary['renewalNote']);
        $this->assertStringStartsWith('Sabre Insurance covers $500. The declared value of this possession is $600.', $viewModel->underInsuranceWarning());
    }

    #[Test]
    public function it_compares_nothing_when_the_value_is_in_another_currency(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insured_value' => '100.00', 'currency' => CurrencyEnum::Euro]);

        $viewModel = new PossessionInsuranceViewModel(user: $user, possession: $dundie);

        $this->assertFalse($viewModel->summary()['isUnderInsured']);
        $this->assertSame('In another currency than the insurance, so not compared', $viewModel->summary()['worthNote']);
        $this->assertNull($viewModel->underInsuranceWarning());
    }

    #[Test]
    public function it_says_when_an_active_record_renews_this_month_or_has_passed(): void
    {
        $this->travelTo('2026-10-27');

        [$user, $dundie] = $this->dundie();
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'starts_on' => null, 'ends_on' => '2026-10-31']);

        $period = fn (): array => new PossessionInsuranceViewModel(user: $user, possession: $dundie->fresh())->records()[0]['parts'][1];

        $this->assertSame('Renews this month', $period()['sub']);

        $record->update(['ends_on' => '2026-10-01']);

        $this->assertSame('Renewal date passed', $period()['sub']);
        $this->assertStringStartsWith('Until ', $period()['value']);
    }

    #[Test]
    public function it_leaves_out_the_record_being_edited_and_fills_the_form_with_it(): void
    {
        [$user, $dundie] = $this->dundie();
        $editing = PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'policy_number' => 'SAB-0001', 'is_listed_individually' => true]);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id]);

        $viewModel = new PossessionInsuranceViewModel(user: $user, possession: $dundie, editing: $editing);

        $this->assertCount(1, $viewModel->records());
        $this->assertTrue($viewModel->form()['isEditing']);
        $this->assertSame('SAB-0001', $viewModel->form()['values']['policy_number']);
        $this->assertTrue($viewModel->form()['isListedIndividually']);
    }

    #[Test]
    public function a_new_record_starts_in_the_currency_of_the_declared_value(): void
    {
        [$user, $dundie] = $this->dundie();

        $form = new PossessionInsuranceViewModel(user: $user, possession: $dundie)->form();

        $this->assertFalse($form['isEditing']);
        $this->assertSame(CurrencyEnum::UsDollar, $form['currency']);
        $this->assertSame('active', $form['values']['status']);
    }

    #[Test]
    public function it_compares_the_insurance_with_the_latest_valuation_rather_than_the_declared_value(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'insurer' => 'Sabre Insurance', 'insured_value' => '500.00', 'ends_on' => null]);
        PossessionValuation::factory()->create(['possession_id' => $dundie->id, 'amount' => '650.00']);

        $viewModel = new PossessionInsuranceViewModel(user: $user, possession: $dundie);
        $summary = $viewModel->summary();

        $this->assertSame('$650', $summary['worth']);
        $this->assertSame('$150 more than it is insured for', $summary['worthNote']);
        $this->assertSame('Sabre Insurance covers $500. The latest valuation of this possession is $650. Ask Sabre Insurance to update the insured value before the next renewal.', $viewModel->underInsuranceWarning());
    }

    #[Test]
    public function it_groups_the_phone_number_of_the_insurer_and_dials_it_as_stored(): void
    {
        [$user, $dundie] = $this->dundie();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $dundie->id, 'contact_phone' => '+15705550123']);

        $record = new PossessionInsuranceViewModel(user: $user, possession: $dundie)->records()[0];

        $this->assertSame('+1 570-555-0123', $record['phone']);
        $this->assertSame('+15705550123', $record['tel']);
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
