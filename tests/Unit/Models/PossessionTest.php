<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PossessionInsuranceStatusEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionTag;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_tags_by_name(): void
    {
        $possession = Possession::factory()->create();
        $office = PossessionTag::factory()->create(['account_id' => $possession->account_id, 'name' => 'office party']);
        $insured = PossessionTag::factory()->create(['account_id' => $possession->account_id, 'name' => 'insured']);

        $possession->tags()->attach([$office->id, $insured->id]);

        $this->assertEquals(['insured', 'office party'], $possession->tags->pluck('name')->all());
    }

    #[Test]
    public function its_acquisition_is_the_earliest_transaction_that_brought_it_in(): void
    {
        $possession = Possession::factory()->create();
        PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Fee, 'occurred_on' => '2001-01-01']);
        PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Sale, 'occurred_on' => '2002-01-01']);
        $inheritance = PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Inheritance, 'occurred_on' => '2019-10-22']);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Purchase, 'occurred_on' => '2005-03-14']);

        $this->assertTrue($possession->acquisitionTransaction()->is($purchase));

        $purchase->delete();

        $this->assertTrue($possession->fresh()->acquisitionTransaction()->is($inheritance));
    }

    #[Test]
    public function an_undated_acquisition_counts_as_the_oldest_and_ties_go_to_the_first_recorded(): void
    {
        $possession = Possession::factory()->create();
        PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'occurred_on' => '1962-03-14']);
        $first = PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::GiftReceived, 'occurred_on' => null]);
        PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Exchange, 'occurred_on' => null]);

        $this->assertTrue($possession->acquisitionTransaction()->is($first));
    }

    #[Test]
    public function without_an_acquisition_transaction_it_has_no_acquisition(): void
    {
        $possession = Possession::factory()->create();
        PossessionTransaction::factory()->create(['possession_id' => $possession->id, 'type' => PossessionTransactionTypeEnum::Shipping]);

        $this->assertNull($possession->acquisitionTransaction());
    }

    #[Test]
    public function its_current_insurance_record_is_the_active_one_that_started_last(): void
    {
        $possession = Possession::factory()->create();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Pending, 'starts_on' => '2027-01-01']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => '2019-11-05']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => null]);
        $sabre = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => '2026-01-01']);

        $this->assertTrue($possession->currentInsuranceRecord()->is($sabre));
    }

    #[Test]
    public function its_current_insurance_record_is_the_latest_recorded_when_none_is_dated(): void
    {
        $possession = Possession::factory()->create();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => null]);
        $latest = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => null]);

        $this->assertTrue($possession->currentInsuranceRecord()->is($latest));
    }

    #[Test]
    public function it_has_no_current_insurance_record_without_an_active_one(): void
    {
        $possession = Possession::factory()->create();
        PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Expired]);

        $this->assertNull($possession->currentInsuranceRecord());
    }

    #[Test]
    public function it_orders_its_insurance_records_by_status_then_latest_start(): void
    {
        $possession = Possession::factory()->create();
        $cancelled = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Cancelled, 'starts_on' => '2026-06-01']);
        $older = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Expired, 'starts_on' => '2015-01-01']);
        $newer = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'status' => PossessionInsuranceStatusEnum::Expired, 'starts_on' => '2019-11-05']);
        $active = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id, 'starts_on' => '2023-01-01']);

        $this->assertEquals(
            [$active->id, $newer->id, $older->id, $cancelled->id],
            $possession->insuranceRecordsInOrder()->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_orders_its_maintenance_records_by_latest_performed_then_latest_recorded(): void
    {
        $possession = Possession::factory()->create();
        $older = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2025-02-01']);
        $sameDayFirst = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2026-03-12']);
        $sameDayLast = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2026-03-12']);

        $this->assertEquals(
            [$sameDayLast->id, $sameDayFirst->id, $older->id],
            $possession->maintenanceRecords->pluck('id')->all(),
        );
    }

    #[Test]
    public function only_its_latest_maintenance_record_can_be_overdue(): void
    {
        $possession = Possession::factory()->create();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2025-01-01', 'next_due_on' => '2025-06-01']);
        $latest = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2026-03-12', 'next_due_on' => '2026-10-01']);

        $this->assertTrue($possession->overdueMaintenanceRecord(CarbonImmutable::parse('2026-10-27'))->is($latest));
        $this->assertNull($possession->overdueMaintenanceRecord(CarbonImmutable::parse('2026-09-30')));
    }

    #[Test]
    public function its_maintenance_record_due_today_is_not_overdue(): void
    {
        $possession = Possession::factory()->create();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2026-03-12', 'next_due_on' => '2026-10-27']);

        $this->assertNull($possession->overdueMaintenanceRecord(CarbonImmutable::parse('2026-10-27 18:00')));
    }

    #[Test]
    public function a_later_maintenance_record_without_a_next_due_date_supersedes_an_overdue_one(): void
    {
        $possession = Possession::factory()->create();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2025-01-01', 'next_due_on' => '2025-06-01']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id, 'performed_on' => '2026-03-12']);

        $this->assertNull($possession->overdueMaintenanceRecord(CarbonImmutable::parse('2026-10-27')));
    }

    #[Test]
    public function its_current_valuation_is_the_latest_by_date_then_the_latest_recorded(): void
    {
        $possession = Possession::factory()->create();
        PossessionValuation::factory()->create(['possession_id' => $possession->id, 'valued_on' => '2026-03-14']);
        $latest = PossessionValuation::factory()->create(['possession_id' => $possession->id, 'valued_on' => '2026-03-14']);
        PossessionValuation::factory()->create(['possession_id' => $possession->id, 'valued_on' => '2019-11-20']);

        $this->assertTrue($possession->currentValuation()->is($latest));
    }

    #[Test]
    public function its_current_value_comes_from_its_current_valuation(): void
    {
        $possession = Possession::factory()->create(['declared_value' => '500.00', 'declared_value_currency' => 'usd']);
        PossessionValuation::factory()->create(['possession_id' => $possession->id, 'amount' => '650.00', 'currency' => 'gbp']);

        $this->assertEquals(['amount' => '650.00', 'currency' => 'gbp', 'isValuation' => true], $possession->currentValue());
    }

    #[Test]
    public function without_a_valuation_its_current_value_is_its_declared_value(): void
    {
        $possession = Possession::factory()->create(['declared_value' => '500.00', 'declared_value_currency' => 'usd']);

        $this->assertEquals(['amount' => '500.00', 'currency' => 'usd', 'isValuation' => false], $possession->currentValue());
    }

    #[Test]
    public function without_a_valuation_or_a_declared_value_it_has_no_current_value(): void
    {
        $possession = Possession::factory()->create(['declared_value' => null, 'declared_value_currency' => null]);

        $this->assertNull($possession->currentValue());
    }

    #[Test]
    public function it_lists_its_stays_with_unknown_arrivals_first_then_by_arrival(): void
    {
        $possession = Possession::factory()->create();
        $cellar = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'arrived_on' => '2024-06-04']);
        $barn = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'arrived_on' => '2019-11-08', 'left_on' => '2024-06-04']);
        $farmhouse = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'arrived_on' => null, 'left_on' => '2019-10-22']);
        $loft = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'arrived_on' => '2019-11-08', 'left_on' => '2019-11-08']);

        $this->assertEquals(
            [$farmhouse->id, $barn->id, $loft->id, $cellar->id],
            $possession->locationStays->pluck('id')->all(),
        );
        $this->assertTrue($possession->openLocationStay->is($cellar));
    }
}
