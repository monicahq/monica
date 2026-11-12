<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Models\PossessionLoan;
use App\Models\PossessionProvenanceEvent;
use Carbon\CarbonImmutable;

/**
 * Keep the two change of custody events of a loan in step with it. A loan in the provenance has one
 * event for its start while it is active or returned, and one for its return while it is returned;
 * any event it should not have is deleted. The events are written in the language of whoever saves
 * the loan, as somebody writing them by hand would.
 */
class PossessionLoanProvenance
{
    public static function synchronise(PossessionLoan $loan): void
    {
        $hasStarted = $loan->is_in_provenance && $loan->status !== PossessionLoanStatusEnum::Planned;
        $hasReturned = $loan->is_in_provenance && $loan->status === PossessionLoanStatusEnum::Returned;

        $loan->departure_provenance_event_id = self::write(
            loan: $loan,
            eventId: $loan->departure_provenance_event_id,
            isWanted: $hasStarted,
            occurredOn: $loan->started_on,
            isDeparture: true,
        );

        $loan->return_provenance_event_id = self::write(
            loan: $loan,
            eventId: $loan->return_provenance_event_id,
            isWanted: $hasReturned,
            occurredOn: $loan->returned_on,
            isDeparture: false,
        );

        $loan->save();
    }

    public static function remove(PossessionLoan $loan): void
    {
        PossessionProvenanceEvent::query()
            ->whereKey(array_filter([$loan->departure_provenance_event_id, $loan->return_provenance_event_id]))
            ->delete();
    }

    private static function write(PossessionLoan $loan, ?int $eventId, bool $isWanted, ?CarbonImmutable $occurredOn, bool $isDeparture): ?int
    {
        $event = $eventId === null ? null : PossessionProvenanceEvent::query()->find($eventId);

        if (! $isWanted || $occurredOn === null) {
            $event?->delete();

            return null;
        }

        $name = $loan->contactName() ?? __('a deleted contact');
        $isLent = $loan->direction === PossessionLoanDirectionEnum::Lent;
        $contactReceivesIt = $isDeparture === $isLent;

        $attributes = [
            'type' => PossessionProvenanceEventTypeEnum::Custody,
            'title' => match (true) {
                $isDeparture && $isLent => __('Lent to :name', ['name' => $name]),
                $isDeparture => __('Borrowed from :name', ['name' => $name]),
                $isLent => __('Returned by :name', ['name' => $name]),
                default => __('Returned to :name', ['name' => $name]),
            },
            'date_precision' => PossessionProvenanceDatePrecisionEnum::Day,
            'occurred_on' => $occurredOn->startOfDay(),
            'from' => $contactReceivesIt ? null : $name,
            'to' => $contactReceivesIt ? $name : null,
            'description' => $isDeparture ? $loan->purpose : null,
        ];

        if ($event instanceof PossessionProvenanceEvent) {
            $event->update($attributes);

            return $event->id;
        }

        return PossessionProvenanceEvent::query()->create([
            'account_id' => $loan->account_id,
            'vault_id' => $loan->vault_id,
            'possession_id' => $loan->possession_id,
            'is_verified' => false,
            ...$attributes,
        ])->id;
    }
}
