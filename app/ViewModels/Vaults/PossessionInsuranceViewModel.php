<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\DateFormatEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The Insurance section of a possession: what it is insured for now, against its current value, when
 * the cover renews, and every insurance record, active first. The current record and the gap are
 * worked out from the records each time; nothing is copied onto the possession.
 */
class PossessionInsuranceViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionInsuranceRecord $editing = null,
    ) {
        $this->possession->load(['insuranceRecords', 'valuations']);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /** @return array{insured: string, insuredNote: string, worth: string, worthNote: string, isUnderInsured: bool, renewal: string, renewalNote: string} */
    public function summary(): array
    {
        $current = $this->possession->currentInsuranceRecord();
        $value = $this->possession->currentValue();
        $worth = $value === null ? __('Unknown') : $this->money($value['amount'], $value['currency']);

        if (! $current instanceof PossessionInsuranceRecord) {
            return [
                'insured' => __('Not insured'),
                'insuredNote' => __('No active record'),
                'worth' => $worth,
                'worthNote' => match (true) {
                    $value === null => __('No value recorded yet.'),
                    $value['isValuation'] => __('The latest valuation of this possession'),
                    default => __('The declared value of this possession'),
                },
                'isUnderInsured' => false,
                'renewal' => '—',
                'renewalNote' => '',
            ];
        }

        return [
            'insured' => $this->money($current->insured_value, $current->currency->value),
            'insuredNote' => $current->is_listed_individually
                ? __(':insurer, listed individually', ['insurer' => $current->insurer])
                : __(':insurer, under general contents', ['insurer' => $current->insurer]),
            'worth' => $worth,
            'worthNote' => $this->gapNote($current),
            'isUnderInsured' => $this->shortfall($current) !== null,
            'renewal' => $current->ends_on === null ? __('No end date') : $this->writeDate($current->ends_on),
            'renewalNote' => $current->ends_on === null ? __('Renews on its own') : $this->renewalNote($current),
        ];
    }

    /**
     * The warning shown when the current record insures the possession for less than its current
     * value, or null when it does not.
     */
    public function underInsuranceWarning(): ?string
    {
        $current = $this->possession->currentInsuranceRecord();

        if (! $current instanceof PossessionInsuranceRecord || $this->shortfall($current) === null) {
            return null;
        }

        $value = $this->possession->currentValue();
        $replacements = [
            'insurer' => $current->insurer,
            'insured' => $this->money($current->insured_value, $current->currency->value),
            'worth' => $this->money((string) $value['amount'], $value['currency'] ?? null),
        ];

        if ($value['isValuation'] ?? false) {
            if ($current->ends_on === null) {
                return __(':insurer covers :insured. The latest valuation of this possession is :worth. Ask :insurer to update the insured value before the next renewal.', $replacements);
            }

            return __(':insurer covers :insured. The latest valuation of this possession is :worth. Ask :insurer to update the insured value before :date.', [
                ...$replacements,
                'date' => $this->writeDate($current->ends_on),
            ]);
        }

        if ($current->ends_on === null) {
            return __(':insurer covers :insured. The declared value of this possession is :worth. Ask :insurer to update the insured value before the next renewal.', $replacements);
        }

        return __(':insurer covers :insured. The declared value of this possession is :worth. Ask :insurer to update the insured value before :date.', [
            ...$replacements,
            'date' => $this->writeDate($current->ends_on),
        ]);
    }

    /**
     * Every record, active first, then pending, expired and cancelled, the latest start first within
     * each. The one being edited is left out, since the form stands in its place.
     *
     * @return list<array{id: string, insurer: string, status: string, tone: string, icon: ?string, coverage: ?string, value: string, parts: list<array{label: string, value: string, isSet: bool, isCode: bool, sub: ?string}>, contactName: ?string, avatarName: string, email: ?string, phone: ?string, tel: ?string, note: ?string, stamp: string, editUrl: string, destroyUrl: string, deleteTitle: string}>
     */
    public function records(): array
    {
        return $this->possession->insuranceRecordsInOrder()
            ->reject(fn (PossessionInsuranceRecord $record): bool => $this->editing?->is($record) ?? false)
            ->map(fn (PossessionInsuranceRecord $record): array => [
                'id' => $record->uuid,
                'insurer' => $record->insurer,
                'status' => __($record->status->label()),
                'tone' => $record->status->tone(),
                'icon' => $record->status->icon(),
                'coverage' => $record->coverage,
                'value' => $this->money($record->insured_value, $record->currency->value),
                'parts' => [
                    ['label' => __('Policy number'), 'value' => $record->policy_number ?? __('Not given yet'), 'isSet' => $record->policy_number !== null, 'isCode' => true, 'sub' => null],
                    ['label' => __('Period'), 'value' => $this->period($record), 'isSet' => true, 'isCode' => false, 'sub' => $this->renewalSub($record)],
                    ['label' => __('Excess'), 'value' => $this->excess($record), 'isSet' => $record->excess !== null, 'isCode' => false, 'sub' => null],
                    ['label' => __('On the policy'), 'value' => $record->is_listed_individually ? __('Listed individually') : __('Under general contents'), 'isSet' => true, 'isCode' => false, 'sub' => null],
                ],
                'contactName' => $record->contact_name,
                'avatarName' => $record->contact_name ?? $record->insurer,
                'email' => $record->contact_email,
                'phone' => $record->contact_phone,
                'tel' => $record->contact_phone === null ? null : str_replace(' ', '', $record->contact_phone),
                'note' => $record->note,
                'stamp' => $this->stamp($record),
                'editUrl' => route('possessions.insuranceRecords.edit', [$this->possession->vault, $this->possession, $record]),
                'destroyUrl' => route('possessions.insuranceRecords.destroy', [$this->possession->vault, $this->possession, $record]),
                'deleteTitle' => __('Delete the :insurer record?', ['insurer' => $record->insurer]),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    public function statuses(): array
    {
        return array_map(
            fn (PossessionInsuranceStatusEnum $status): array => ['value' => $status->value, 'label' => __($status->label())],
            PossessionInsuranceStatusEnum::cases(),
        );
    }

    /**
     * The currency a new record starts in: the one of the latest record, else the one of the declared
     * value, else none, so a currency is never guessed from the language.
     */
    public function preselectedCurrency(): ?CurrencyEnum
    {
        $latest = $this->possession->insuranceRecords->sortByDesc('id')->first();

        return $latest->currency ?? CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency);
    }

    /**
     * What the form starts with: the record being edited, else an active record in the currency last
     * used.
     *
     * @return array{isEditing: bool, action: string, method: string, cancelUrl: ?string, values: array<string, string>, isListedIndividually: bool, currency: ?CurrencyEnum}
     */
    public function form(): array
    {
        $editing = $this->editing;

        if (! $editing instanceof PossessionInsuranceRecord) {
            return [
                'isEditing' => false,
                'action' => route('possessions.insuranceRecords.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'values' => ['status' => PossessionInsuranceStatusEnum::Active->value],
                'isListedIndividually' => false,
                'currency' => $this->preselectedCurrency(),
            ];
        }

        return [
            'isEditing' => true,
            'action' => route('possessions.insuranceRecords.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.insuranceRecords.index', [$this->possession->vault, $this->possession]),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'insurer' => $editing->insurer,
                'status' => $editing->status->value,
                'policy_number' => $editing->policy_number,
                'coverage' => $editing->coverage,
                'insured_value' => $editing->insured_value,
                'excess' => $editing->excess,
                'starts_on' => $editing->starts_on?->toDateString(),
                'ends_on' => $editing->ends_on?->toDateString(),
                'contact_name' => $editing->contact_name,
                'contact_email' => $editing->contact_email,
                'contact_phone' => $editing->contact_phone,
                'note' => $editing->note,
            ]),
            'isListedIndividually' => $editing->is_listed_individually,
            'currency' => $editing->currency,
        ];
    }

    /**
     * How much more the possession is worth than the current record insures it for, or null when it
     * is not under-insured or the two cannot be compared because a value is missing or the currencies
     * differ.
     */
    private function shortfall(PossessionInsuranceRecord $current): ?string
    {
        $value = $this->possession->currentValue();

        if ($value === null || $value['currency'] !== $current->currency->value) {
            return null;
        }

        $shortfall = bcsub($value['amount'], $current->insured_value, 2);

        return bccomp($shortfall, '0', 2) > 0 ? $shortfall : null;
    }

    private function gapNote(PossessionInsuranceRecord $current): string
    {
        $value = $this->possession->currentValue();

        if ($value === null) {
            return __('No value recorded yet.');
        }

        if ($value['currency'] !== $current->currency->value) {
            return __('In another currency than the insurance, so not compared');
        }

        $shortfall = $this->shortfall($current);

        if ($shortfall === null) {
            return __('Insured for at least what it is worth');
        }

        return __(':amount more than it is insured for', ['amount' => $this->money($shortfall, $current->currency->value)]);
    }

    private function renewalNote(PossessionInsuranceRecord $current): string
    {
        $months = $this->monthsUntil($current->ends_on);

        if ($months < 0) {
            return __('Passed, with :insurer', ['insurer' => $current->insurer]);
        }

        if ($months === 0) {
            return __('This month, with :insurer', ['insurer' => $current->insurer]);
        }

        return trans_choice('In :count month, with :insurer|In :count months, with :insurer', $months, ['insurer' => $current->insurer]);
    }

    private function renewalSub(PossessionInsuranceRecord $record): ?string
    {
        if ($record->status !== PossessionInsuranceStatusEnum::Active || $record->ends_on === null) {
            return null;
        }

        $months = $this->monthsUntil($record->ends_on);

        if ($months < 0) {
            return __('Renewal date passed');
        }

        if ($months === 0) {
            return __('Renews this month');
        }

        return trans_choice('Renews in :count month|Renews in :count months', $months);
    }

    /**
     * Calendar months from this month to the month of the date, in the user's timezone: zero for a
     * date later this month, below zero once the date has passed.
     */
    private function monthsUntil(CarbonImmutable $date): int
    {
        $today = CarbonImmutable::today($this->user->timezone);

        if ($date->toDateString() < $today->toDateString()) {
            return -1;
        }

        return ($date->year - $today->year) * 12 + ($date->month - $today->month);
    }

    private function period(PossessionInsuranceRecord $record): string
    {
        return match (true) {
            $record->starts_on !== null && $record->ends_on !== null => __(':start to :end', ['start' => $this->writeDate($record->starts_on), 'end' => $this->writeDate($record->ends_on)]),
            $record->starts_on !== null => __('From :date', ['date' => $this->writeDate($record->starts_on)]),
            $record->ends_on !== null => __('Until :date', ['date' => $this->writeDate($record->ends_on)]),
            default => __('No dates recorded'),
        };
    }

    private function excess(PossessionInsuranceRecord $record): string
    {
        if ($record->excess === null) {
            return __('Not recorded');
        }

        if (bccomp($record->excess, '0', 2) === 0) {
            return __('None');
        }

        return $this->money($record->excess, $record->currency->value);
    }

    private function money(string $amount, ?string $currency): string
    {
        return new PossessionValueFormatter($this->user)->formatExact($amount, $currency);
    }

    private function stamp(PossessionInsuranceRecord $record): string
    {
        $added = __('Added on :date', ['date' => $this->writeDate($record->created_at)]);

        if ($record->updated_at === null || $record->updated_at->equalTo($record->created_at)) {
            return $added;
        }

        return __(':added, last edited :when', ['added' => $added, 'when' => $record->updated_at->diffForHumans()]);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
