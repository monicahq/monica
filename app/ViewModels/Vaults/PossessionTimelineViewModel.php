<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionTimelineEntryKindEnum;
use App\Enums\PossessionTimelineSectionEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\LocationTree;
use App\Helpers\PossessionTimeline;
use App\Helpers\PossessionTimelineEntry;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionLoan;
use App\Models\PossessionLocationStay;
use App\Models\PossessionMaintenanceRecord;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\User;
use Carbon\CarbonInterface;

class PossessionTimelineViewModel
{
    private readonly PossessionTimeline $timeline;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionTimelineSectionEnum $section = null,
    ) {
        $this->timeline = new PossessionTimeline($this->possession);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /** @return list<array{label: string, count: int, url: string, isCurrent: bool}> */
    public function chips(): array
    {
        $counts = $this->timeline->counts();

        if ($counts === []) {
            return [];
        }

        $chips = [[
            'label' => __('All'),
            'count' => array_sum($counts),
            'url' => $this->url(null),
            'isCurrent' => $this->section === null,
        ]];

        foreach (PossessionTimelineSectionEnum::cases() as $section) {
            if (($counts[$section->value] ?? 0) === 0) {
                continue;
            }

            $chips[] = [
                'label' => __($section->label()),
                'count' => $counts[$section->value],
                'url' => $this->url($section),
                'isCurrent' => $this->section === $section,
            ];
        }

        return $chips;
    }

    /** @return list<array{title: string, count: string, entries: list<array<string, mixed>>}> */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->timeline->entries($this->section) as $index => $entry) {
            $key = $entry->date?->format('Y') ?? 'undated';
            $groups[$key] ??= ['title' => $key === 'undated' ? __('Date unknown') : $key, 'entries' => []];
            $groups[$key]['entries'][] = $this->entry($entry, $index);
        }

        return array_values(array_map(fn (array $group): array => [
            ...$group,
            'count' => trans_choice(':count entry|:count entries', count($group['entries'])),
        ], $groups));
    }

    public function emptyLine(): string
    {
        if ($this->section === null) {
            return __('Nothing recorded in any section yet.');
        }

        return __('Nothing recorded in :section yet.', ['section' => mb_strtolower(__($this->section->label()))]);
    }

    private function url(?PossessionTimelineSectionEnum $section): string
    {
        return route('possessions.timeline.index', array_filter([
            'vault' => $this->possession->vault,
            'possession' => $this->possession,
            'section' => $section?->value,
        ]));
    }

    /** @return array{id: string, icon: string, colour: string, noun: string, date: string, title: string, url: string, badge: string|null, amount: string|null, amountNote: string|null, detail: string|null} */
    private function entry(PossessionTimelineEntry $entry, int $index): array
    {
        $words = match (true) {
            $entry->record instanceof PossessionTransaction => $this->transaction($entry->record),
            $entry->record instanceof PossessionInsuranceRecord => $this->insurance($entry->record),
            $entry->record instanceof PossessionValuation => $this->valuation($entry->record),
            $entry->record instanceof PossessionProvenanceEvent => $this->provenance($entry->record),
            $entry->record instanceof PossessionMaintenanceRecord => $this->maintenance($entry->record),
            $entry->record instanceof PossessionLoan => $this->loan($entry->record, $entry->kind),
            $entry->record instanceof PossessionLocationStay => $this->stay($entry->record),
        };

        return [
            'id' => "timeline-entry-{$index}",
            'icon' => $entry->section->icon(),
            'colour' => $entry->section->colour()->iconForeground(),
            'noun' => __($entry->section->noun()),
            'date' => $this->dateOf($entry),
            'url' => $this->urlOf($entry),
            'badge' => $this->badge($entry),
            ...$words,
        ];
    }

    private function urlOf(PossessionTimelineEntry $entry): string
    {
        $url = route($entry->section->routeName(), [$this->possession->vault, $this->possession]);
        $prefix = $entry->section->anchorPrefix();

        return $prefix === null ? $url : "{$url}#{$prefix}-{$entry->record->uuid}-title";
    }

    private function badge(PossessionTimelineEntry $entry): ?string
    {
        return match (true) {
            $entry->isAcquisition => __('Acquisition'),
            $entry->isCurrent && $entry->section === PossessionTimelineSectionEnum::Valuations => __('Current value'),
            $entry->isCurrent && $entry->section === PossessionTimelineSectionEnum::Locations => __('Here now'),
            default => null,
        };
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function transaction(PossessionTransaction $transaction): array
    {
        $counterparty = $transaction->counterparty === null
            ? null
            : __(':label: :name', ['label' => __($transaction->type->counterpartyLabel()), 'name' => $transaction->counterparty]);

        return [
            'title' => __($transaction->type->label()),
            'detail' => $this->joined([$counterparty, $transaction->note]),
            'amount' => $transaction->total() === null ? null : $this->money($transaction->total(), $transaction->currency?->value),
            'amountNote' => $transaction->total() === null ? null : __('Total'),
        ];
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function insurance(PossessionInsuranceRecord $record): array
    {
        return [
            'title' => __('Covered by :insurer', ['insurer' => $record->insurer]),
            'detail' => $this->joined([$record->coverage, $record->note]),
            'amount' => $this->money($record->insured_value, $record->currency->value),
            'amountNote' => __('Insured for'),
        ];
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function valuation(PossessionValuation $valuation): array
    {
        return [
            'title' => __($valuation->type->label()),
            'detail' => $this->joined([$valuation->valuer, $valuation->note]),
            'amount' => $this->money($valuation->amount, $valuation->currency->value),
            'amountNote' => $this->changeFromThePrevious($valuation),
        ];
    }

    private function changeFromThePrevious(PossessionValuation $valuation): ?string
    {
        $valuations = $this->possession->valuations->values();
        $previous = $valuations->get($valuations->search(fn (PossessionValuation $other): bool => $other->is($valuation)) + 1);

        if (! $previous instanceof PossessionValuation || $previous->currency !== $valuation->currency) {
            return null;
        }

        $difference = bcsub($valuation->amount, $previous->amount, 2);

        return match (bccomp($difference, '0', 2)) {
            1 => __('Up :amount', ['amount' => $this->money($difference, $valuation->currency->value)]),
            -1 => __('Down :amount', ['amount' => $this->money(mb_ltrim($difference, '-'), $valuation->currency->value)]),
            default => __('Unchanged'),
        };
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function provenance(PossessionProvenanceEvent $event): array
    {
        return [
            'title' => $event->title ?? __($event->type->label()),
            'detail' => $this->joined([$event->from, $event->to, $event->location, $event->description]),
            'amount' => null,
            'amountNote' => null,
        ];
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function maintenance(PossessionMaintenanceRecord $record): array
    {
        return [
            'title' => $record->title,
            'detail' => $this->joined([$record->performed_by, $record->description]),
            'amount' => $record->cost === null ? null : $this->money($record->cost, $record->currency?->value),
            'amountNote' => $record->cost === null ? null : __('Paid'),
        ];
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function loan(PossessionLoan $loan, PossessionTimelineEntryKindEnum $kind): array
    {
        $name = $loan->contactName() ?? __('Somebody no longer in this vault');

        if ($kind === PossessionTimelineEntryKindEnum::LoanReturned) {
            return [
                'title' => $loan->direction === PossessionLoanDirectionEnum::Lent
                    ? __('Returned by :name', ['name' => $name])
                    : __('Given back to :name', ['name' => $name]),
                'detail' => null,
                'amount' => null,
                'amountNote' => null,
            ];
        }

        return [
            'title' => $loan->direction === PossessionLoanDirectionEnum::Lent
                ? __('Lent to :name', ['name' => $name])
                : __('Borrowed from :name', ['name' => $name]),
            'detail' => $loan->purpose,
            'amount' => null,
            'amountNote' => null,
        ];
    }

    /** @return array{title: string, detail: string|null, amount: string|null, amountNote: string|null} */
    private function stay(PossessionLocationStay $stay): array
    {
        $tree = once(fn (): LocationTree => LocationTree::forAccount($this->possession->account_id));
        $place = $tree->path($stay->location);
        $isFirst = $this->possession->locationStays->first()?->is($stay) ?? false;

        return [
            'title' => $isFirst ? __('Kept in :place', ['place' => $place]) : __('Moved to :place', ['place' => $place]),
            'detail' => $this->joined([$stay->reason, $stay->note]),
            'amount' => null,
            'amountNote' => null,
        ];
    }

    private function dateOf(PossessionTimelineEntry $entry): string
    {
        if ($entry->date === null) {
            return __('Date unknown');
        }

        return match ($entry->precision) {
            PossessionProvenanceDatePrecisionEnum::Unknown => __('Date unknown'),
            PossessionProvenanceDatePrecisionEnum::Day => $this->writeDate($entry->date),
            PossessionProvenanceDatePrecisionEnum::Month => $entry->date->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
            PossessionProvenanceDatePrecisionEnum::Year => $entry->date->format('Y'),
            PossessionProvenanceDatePrecisionEnum::Circa => __('About :year', ['year' => $entry->date->format('Y')]),
        };
    }

    /** @param  list<string|null>  $parts */
    private function joined(array $parts): ?string
    {
        $parts = array_values(array_filter($parts, fn (?string $part): bool => $part !== null && mb_trim($part) !== ''));

        return $parts === [] ? null : implode(' · ', $parts);
    }

    private function money(string $amount, ?string $currency): string
    {
        return new PossessionValueFormatter($this->user)->formatExact($amount, $currency);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
