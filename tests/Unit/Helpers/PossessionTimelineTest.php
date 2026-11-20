<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionTimelineEntryKindEnum;
use App\Enums\PossessionTimelineSectionEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Helpers\PossessionTimeline;
use App\Helpers\PossessionTimelineEntry;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTimelineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_one_entry_per_record_of_every_section_newest_first(): void
    {
        $costume = Possession::factory()->create(['name' => 'Holiday Armadillo costume']);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $costume->id, 'date_precision' => PossessionProvenanceDatePrecisionEnum::Year, 'occurred_on' => '2024-01-01']);
        PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'occurred_on' => '2025-06-28']);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2025-12-20']);
        PossessionInsuranceRecord::factory()->create(['possession_id' => $costume->id, 'starts_on' => '2026-03-03']);
        PossessionValuation::factory()->create(['possession_id' => $costume->id, 'valued_on' => '2026-10-26']);
        PossessionLocationStay::factory()->create(['possession_id' => $costume->id, 'arrived_on' => '2026-11-17']);

        $sections = array_map(fn (PossessionTimelineEntry $entry): string => $entry->section->value, $this->timeline($costume)->entries());

        $this->assertSame(['locations', 'valuations', 'insurance', 'maintenance', 'transactions', 'provenance'], $sections);
    }

    #[Test]
    public function it_reads_a_returned_loan_as_two_entries_and_a_planned_one_as_none(): void
    {
        $costume = Possession::factory()->create();
        PossessionLoan::factory()->returned()->create(['possession_id' => $costume->id, 'started_on' => '2026-10-12', 'returned_on' => '2026-11-02']);
        PossessionLoan::factory()->planned()->create(['possession_id' => $costume->id, 'started_on' => '2026-12-20']);

        $kinds = array_map(fn (PossessionTimelineEntry $entry): PossessionTimelineEntryKindEnum => $entry->kind, $this->timeline($costume)->entries());

        $this->assertSame([PossessionTimelineEntryKindEnum::LoanReturned, PossessionTimelineEntryKindEnum::LoanStarted], $kinds);
    }

    #[Test]
    public function it_leaves_out_a_provenance_event_a_loan_or_a_transaction_already_gives(): void
    {
        $costume = Possession::factory()->create();
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $costume->id]);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $costume->id, 'possession_transaction_id' => $purchase->id]);
        $departure = PossessionProvenanceEvent::factory()->create(['possession_id' => $costume->id]);
        PossessionLoan::factory()->create(['possession_id' => $costume->id, 'is_in_provenance' => true, 'departure_provenance_event_id' => $departure->id]);

        $this->assertSame([], $this->timeline($costume)->entries(PossessionTimelineSectionEnum::Provenance));
    }

    #[Test]
    public function it_puts_what_has_no_date_last(): void
    {
        $costume = Possession::factory()->create();
        PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'occurred_on' => null]);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $costume->id, 'date_precision' => PossessionProvenanceDatePrecisionEnum::Unknown]);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2025-12-20']);

        $entries = $this->timeline($costume)->entries();

        $this->assertSame('maintenance', $entries[0]->section->value);
        $this->assertNull($entries[1]->date);
        $this->assertNull($entries[2]->date);
    }

    #[Test]
    public function it_puts_the_latest_recorded_first_on_the_same_day(): void
    {
        $costume = Possession::factory()->create();
        $first = PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2025-12-20', 'title' => 'Repair']);
        $second = PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2025-12-20', 'title' => 'Cleaning']);

        $records = array_map(fn (PossessionTimelineEntry $entry): int => $entry->record->id, $this->timeline($costume)->entries());

        $this->assertSame([$second->id, $first->id], $records);
    }

    #[Test]
    public function it_marks_the_current_value_the_place_it_is_now_and_how_it_came_in(): void
    {
        $costume = Possession::factory()->create();
        PossessionValuation::factory()->create(['possession_id' => $costume->id, 'valued_on' => '2025-01-01']);
        $latest = PossessionValuation::factory()->create(['possession_id' => $costume->id, 'valued_on' => '2026-10-26']);
        PossessionLocationStay::factory()->create(['possession_id' => $costume->id, 'arrived_on' => '2025-06-28', 'left_on' => '2026-11-17']);
        $here = PossessionLocationStay::factory()->create(['possession_id' => $costume->id, 'arrived_on' => '2026-11-17', 'left_on' => null]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'type' => PossessionTransactionTypeEnum::Purchase]);
        PossessionTransaction::factory()->create(['possession_id' => $costume->id, 'type' => PossessionTransactionTypeEnum::Fee]);

        $entries = collect($this->timeline($costume)->entries());

        $marked = fn (string $flag): array => $entries
            ->filter(fn (PossessionTimelineEntry $entry): bool => $entry->{$flag})
            ->map(fn (PossessionTimelineEntry $entry): string => "{$entry->section->value}:{$entry->record->id}")
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(["valuations:{$latest->id}", "locations:{$here->id}"], $marked('isCurrent'));
        $this->assertSame(["transactions:{$purchase->id}"], $marked('isAcquisition'));
    }

    #[Test]
    public function it_counts_every_section_and_narrows_to_one(): void
    {
        $costume = Possession::factory()->create();
        PossessionLoan::factory()->returned()->create(['possession_id' => $costume->id]);
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id]);

        $timeline = $this->timeline($costume);

        $this->assertEquals(['loans' => 2, 'maintenance' => 1], $timeline->counts());
        $this->assertCount(1, $timeline->entries(PossessionTimelineSectionEnum::Maintenance));
    }

    private function timeline(Possession $possession): PossessionTimeline
    {
        return new PossessionTimeline($possession->fresh());
    }
}
