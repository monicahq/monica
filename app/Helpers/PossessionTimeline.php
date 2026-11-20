<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionLoanStatusEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionTimelineEntryKindEnum;
use App\Enums\PossessionTimelineSectionEnum;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use Illuminate\Support\Collection;

/**
 * Everything that happened to one possession, read from its sections and stored nowhere: one entry
 * per record, two for a loan that came back, newest first, undated last. A provenance event that a
 * loan or a transaction wrote is left out, since that loan or transaction already gives the moment.
 */
class PossessionTimeline
{
    public function __construct(
        private readonly Possession $possession,
    ) {}

    /** @return list<PossessionTimelineEntry> */
    public function entries(?PossessionTimelineSectionEnum $section = null): array
    {
        return $this->all()
            ->when($section !== null, fn (Collection $entries): Collection => $entries->filter(fn (PossessionTimelineEntry $entry): bool => $entry->section === $section))
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return $this->all()
            ->countBy(fn (PossessionTimelineEntry $entry): string => $entry->section->value)
            ->all();
    }

    /** @return Collection<int, PossessionTimelineEntry> */
    private function all(): Collection
    {
        return once(fn (): Collection => collect([
            ...$this->transactions(),
            ...$this->insurance(),
            ...$this->valuations(),
            ...$this->provenance(),
            ...$this->maintenance(),
            ...$this->loans(),
            ...$this->locations(),
        ])->sort($this->newestFirst(...))->values());
    }

    private function newestFirst(PossessionTimelineEntry $a, PossessionTimelineEntry $b): int
    {
        if ($a->date === null && $b->date !== null) {
            return 1;
        }

        if ($b->date === null && $a->date !== null) {
            return -1;
        }

        if ($a->date !== null && $b->date !== null && ! $a->date->isSameDay($b->date)) {
            return $b->date <=> $a->date;
        }

        return [$b->record->created_at, $b->record->id, $this->laterOnTheSameRecord($b)] <=> [$a->record->created_at, $a->record->id, $this->laterOnTheSameRecord($a)];
    }

    private function laterOnTheSameRecord(PossessionTimelineEntry $entry): int
    {
        return $entry->kind === PossessionTimelineEntryKindEnum::LoanReturned ? 1 : 0;
    }

    /** @return list<PossessionTimelineEntry> */
    private function transactions(): array
    {
        return $this->possession->transactions
            ->map(fn (PossessionTransaction $transaction): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Transactions,
                kind: PossessionTimelineEntryKindEnum::Transaction,
                record: $transaction,
                date: $transaction->occurred_on,
                isAcquisition: $transaction->type->isAcquisition(),
            ))
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function insurance(): array
    {
        return $this->possession->insuranceRecords
            ->map(fn (PossessionInsuranceRecord $record): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Insurance,
                kind: PossessionTimelineEntryKindEnum::Insurance,
                record: $record,
                date: $record->starts_on,
            ))
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function valuations(): array
    {
        $current = $this->possession->currentValuation();

        return $this->possession->valuations
            ->map(fn (PossessionValuation $valuation): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Valuations,
                kind: PossessionTimelineEntryKindEnum::Valuation,
                record: $valuation,
                date: $valuation->valued_on,
                isCurrent: $current?->is($valuation) ?? false,
            ))
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function provenance(): array
    {
        $writtenByLoans = $this->possession->loans
            ->flatMap(fn (PossessionLoan $loan): array => [$loan->departure_provenance_event_id, $loan->return_provenance_event_id])
            ->filter()
            ->all();

        return $this->possession->provenanceEvents
            ->reject(fn (PossessionProvenanceEvent $event): bool => $event->possession_transaction_id !== null || in_array($event->id, $writtenByLoans, true))
            ->map(fn (PossessionProvenanceEvent $event): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Provenance,
                kind: PossessionTimelineEntryKindEnum::Provenance,
                record: $event,
                date: $event->date_precision === PossessionProvenanceDatePrecisionEnum::Unknown ? null : $event->occurred_on,
                precision: $event->date_precision,
            ))
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function maintenance(): array
    {
        return $this->possession->maintenanceRecords
            ->map(fn (PossessionMaintenanceRecord $record): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Maintenance,
                kind: PossessionTimelineEntryKindEnum::Maintenance,
                record: $record,
                date: $record->performed_on,
            ))
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function loans(): array
    {
        return $this->possession->loans
            ->reject(fn (PossessionLoan $loan): bool => $loan->status === PossessionLoanStatusEnum::Planned)
            ->flatMap(function (PossessionLoan $loan): array {
                $entries = [new PossessionTimelineEntry(
                    section: PossessionTimelineSectionEnum::Loans,
                    kind: PossessionTimelineEntryKindEnum::LoanStarted,
                    record: $loan,
                    date: $loan->started_on,
                )];

                if ($loan->status === PossessionLoanStatusEnum::Returned) {
                    $entries[] = new PossessionTimelineEntry(
                        section: PossessionTimelineSectionEnum::Loans,
                        kind: PossessionTimelineEntryKindEnum::LoanReturned,
                        record: $loan,
                        date: $loan->returned_on,
                    );
                }

                return $entries;
            })
            ->all();
    }

    /** @return list<PossessionTimelineEntry> */
    private function locations(): array
    {
        return $this->possession->locationStays
            ->map(fn (PossessionLocationStay $stay): PossessionTimelineEntry => new PossessionTimelineEntry(
                section: PossessionTimelineSectionEnum::Locations,
                kind: PossessionTimelineEntryKindEnum::Stay,
                record: $stay,
                date: $stay->arrived_on,
                isCurrent: $stay->left_on === null,
            ))
            ->all();
    }
}
