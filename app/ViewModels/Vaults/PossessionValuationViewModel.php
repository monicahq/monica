<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\DateFormatEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FlagState;
use App\Helpers\PossessionValueFormatter;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\PossessionValuation;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

/**
 * The Valuations section of a possession: what it is worth now, how that moved from the valuation
 * before, what it is insured for, and every valuation, the newest first. The current value and every
 * change are worked out from the valuations each time; nothing is copied onto the possession.
 */
class PossessionValuationViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
    ) {
        $this->possession->load(['valuations.documents', 'insuranceRecords']);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    public function createUrl(): string
    {
        return route('possessions.valuations.create', [$this->possession->vault, $this->possession]);
    }

    public function canAttachDocuments(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
    }

    /** @return array{current: string, currentNote: string, change: string, changeNote: string, insured: string, insuredNote: string, isUnderInsured: bool} */
    public function summary(): array
    {
        $current = $this->possession->currentValuation();
        $previous = $this->possession->valuations->get(1);
        $insurance = $this->possession->currentInsuranceRecord();
        $shortfall = $current instanceof PossessionValuation && $insurance instanceof PossessionInsuranceRecord
            ? $this->difference($current->amount, $current->currency, $insurance->insured_value, $insurance->currency)
            : null;

        return [
            'current' => $current instanceof PossessionValuation ? $this->money($current->amount, $current->currency) : '—',
            'currentNote' => $current instanceof PossessionValuation
                ? __(':type, :date', ['type' => __($current->type->label()), 'date' => $this->writeDate($current->valued_on)])
                : __('No valuation yet'),
            'change' => $current instanceof PossessionValuation && $previous instanceof PossessionValuation
                ? $this->changeText($current, $previous) ?? '—'
                : '—',
            'changeNote' => match (true) {
                ! $previous instanceof PossessionValuation => __('Only one valuation so far'),
                $previous->currency !== $current?->currency => __('Different currencies'),
                default => __('From :amount, :date', ['amount' => $this->money($previous->amount, $previous->currency), 'date' => $this->writeDate($previous->valued_on)]),
            },
            'insured' => $insurance instanceof PossessionInsuranceRecord ? $this->money($insurance->insured_value, $insurance->currency) : __('Not insured'),
            'insuredNote' => match (true) {
                ! $insurance instanceof PossessionInsuranceRecord => __('No active record'),
                $shortfall !== null && bccomp($shortfall, '0', 2) > 0 => __(':amount below the current value', ['amount' => $this->money($shortfall, $insurance->currency)]),
                default => $insurance->insurer,
            },
            'isUnderInsured' => $shortfall !== null && bccomp($shortfall, '0', 2) > 0,
        ];
    }

    /**
     * Every valuation, the newest first, with the change from the one before it by date.
     *
     * @return list<array{id: string, type: string, icon: string, isCurrent: bool, date: string, valuer: ?string, amount: string, change: string, parts: list<array{label: string, value: string, isSet: bool, isCode: bool, isLink: bool}>, note: ?string, documents: list<array{name: string, meta: string, url: string}>, stamp: string, destroyUrl: string, deleteTitle: string, deleteWarning: string}>
     */
    public function valuations(): array
    {
        $valuations = $this->possession->valuations->values();

        return $valuations
            ->map(function (PossessionValuation $valuation, int $index) use ($valuations): array {
                $previous = $valuations->get($index + 1);

                return [
                    'id' => $valuation->uuid,
                    'type' => __($valuation->type->label()),
                    'icon' => $valuation->type->icon(),
                    'isCurrent' => $index === 0,
                    'date' => $this->writeDate($valuation->valued_on),
                    'valuer' => $valuation->valuer,
                    'amount' => $this->money($valuation->amount, $valuation->currency),
                    'change' => match (true) {
                        ! $previous instanceof PossessionValuation => __('First valuation'),
                        $previous->currency !== $valuation->currency => __('In :currency', ['currency' => mb_strtoupper($valuation->currency->value)]),
                        default => __(':change from the previous', ['change' => $this->changeText($valuation, $previous)]),
                    },
                    'parts' => [
                        $this->part(__('Confidence'), $valuation->confidence === PossessionValuationConfidenceEnum::Unknown ? null : __($valuation->confidence->label())),
                        $this->part(__('Method'), $valuation->method),
                        $this->part(__('Reference'), $valuation->reference_number, isCode: true),
                        $this->part(__('Source link'), $valuation->source_url, isLink: true),
                    ],
                    'note' => $valuation->note,
                    'documents' => $valuation->documents->map(fn (File $file): array => [
                        'name' => $file->original_name,
                        'meta' => __(':kind, :size', ['kind' => mb_strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION) ?: $file->mime_type), 'size' => Number::fileSize($file->size_in_bytes)]),
                        'url' => route('files.show', [$this->possession->vault, $file]),
                    ])->values()->all(),
                    'stamp' => __('Added on :date', ['date' => $this->writeDate($valuation->created_at)]),
                    'destroyUrl' => route('possessions.valuations.destroy', [$this->possession->vault, $this->possession, $valuation]),
                    'deleteTitle' => __('Delete the :type of :date?', ['type' => mb_strtolower(__($valuation->type->label())), 'date' => $this->writeDate($valuation->valued_on)]),
                    'deleteWarning' => $this->deleteWarning($valuation),
                ];
            })
            ->all();
    }

    /**
     * What deleting a valuation changes, said before it happens: the current value it leaves, and
     * the documents that go with it.
     */
    public function deleteWarning(PossessionValuation $valuation): string
    {
        $current = $this->possession->currentValuation();
        $next = $this->possession->valuations->first(fn (PossessionValuation $other): bool => ! $other->is($valuation));

        $sentences = [match (true) {
            ! $current?->is($valuation) => __('The current value stays :amount.', ['amount' => $this->money($current->amount, $current->currency)]),
            ! $next instanceof PossessionValuation => __('The possession will no longer have a valuation.'),
            default => __('The current value goes back to :amount, from the :type of :date.', [
                'amount' => $this->money($next->amount, $next->currency),
                'type' => mb_strtolower(__($next->type->label())),
                'date' => $this->writeDate($next->valued_on),
            ]),
        }];

        $documents = $valuation->documents->count();

        $sentences[] = $documents === 0
            ? __('To correct a figure, record a new valuation instead. This cannot be undone.')
            : trans_choice(':count attached document goes with it. This cannot be undone.|:count attached documents go with it. This cannot be undone.', $documents);

        return implode(' ', $sentences);
    }

    /** @return list<array{value: string, label: string}> */
    public function types(): array
    {
        return array_map(
            fn (PossessionValuationTypeEnum $type): array => ['value' => $type->value, 'label' => __($type->label())],
            PossessionValuationTypeEnum::cases(),
        );
    }

    /** @return list<array{value: string, label: string}> */
    public function confidences(): array
    {
        return array_map(
            fn (PossessionValuationConfidenceEnum $confidence): array => ['value' => $confidence->value, 'label' => __($confidence->label())],
            PossessionValuationConfidenceEnum::cases(),
        );
    }

    /**
     * The currency a new valuation starts in: the one of the current valuation, else the one of the
     * declared value, else none, so a currency is never guessed from the language.
     */
    public function preselectedCurrency(): ?CurrencyEnum
    {
        return $this->possession->currentValuation()->currency
            ?? CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency);
    }

    /**
     * What the form needs to say, as the amount is typed, how a new valuation changes the current
     * value. The sentences are translated here; the form only picks one and fills in the difference.
     *
     * @return array{date: ?string, amount: ?string, currency: ?string, empty: string, first: string, earlier: string, otherCurrency: string, up: string, down: string, unchanged: string}
     */
    public function preview(): array
    {
        $current = $this->possession->currentValuation();
        $amount = $current instanceof PossessionValuation ? $this->money($current->amount, $current->currency) : '';

        return [
            'date' => $current?->valued_on->toDateString(),
            'amount' => $current?->amount,
            'currency' => $current?->currency->value,
            'empty' => __('Enter an amount to see how this changes the current value.'),
            'first' => __('This becomes the current value.'),
            'earlier' => $current instanceof PossessionValuation
                ? __('Dated before :date, so it goes into the history and the current value stays :amount.', ['date' => $this->writeDate($current->valued_on), 'amount' => $amount])
                : '',
            'otherCurrency' => __('This becomes the current value. It is in another currency, so it is not compared with :amount.', ['amount' => $amount]),
            'up' => __('This becomes the current value, up :difference from :amount.', ['amount' => $amount]),
            'down' => __('This becomes the current value, down :difference from :amount.', ['amount' => $amount]),
            'unchanged' => __('This becomes the current value, unchanged from :amount.', ['amount' => $amount]),
        ];
    }

    /**
     * How the value moved from one valuation to the one before it, or null when the two cannot be
     * compared because their currencies differ.
     */
    private function changeText(PossessionValuation $valuation, PossessionValuation $previous): ?string
    {
        $difference = $this->difference($valuation->amount, $valuation->currency, $previous->amount, $previous->currency);

        if ($difference === null) {
            return null;
        }

        return match (bccomp($difference, '0', 2)) {
            1 => __('Up :amount', ['amount' => $this->money($difference, $valuation->currency)]),
            -1 => __('Down :amount', ['amount' => $this->money(mb_ltrim($difference, '-'), $valuation->currency)]),
            default => __('Unchanged'),
        };
    }

    private function difference(string $amount, CurrencyEnum $currency, string $other, CurrencyEnum $otherCurrency): ?string
    {
        if ($currency !== $otherCurrency) {
            return null;
        }

        return bcsub($amount, $other, 2);
    }

    /** @return array{label: string, value: string, isSet: bool, isCode: bool, isLink: bool} */
    private function part(string $label, ?string $value, bool $isCode = false, bool $isLink = false): array
    {
        return [
            'label' => $label,
            'value' => $value ?? __('Not recorded'),
            'isSet' => $value !== null,
            'isCode' => $isCode,
            'isLink' => $isLink && $value !== null,
        ];
    }

    private function money(string $amount, CurrencyEnum $currency): string
    {
        return new PossessionValueFormatter($this->user)->formatExact($amount, $currency->value);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
