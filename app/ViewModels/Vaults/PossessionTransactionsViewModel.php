<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\CurrencyEnum;
use App\Enums\DateFormatEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\FlagState;
use App\Helpers\PossessionValueFormatter;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Number;

/**
 * The Transactions section of a possession: when and how it came in and at what price, what it has
 * cost since, and every transaction, newest first. The acquisition and the costs are worked out from
 * the transactions each time; nothing is copied onto the possession.
 */
class PossessionTransactionsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionTransaction $editing = null,
    ) {
        $this->possession->load('transactions.documents');
    }

    public function canAttachDocuments(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /** @return array{acquired: string, how: string, price: string, priceNote: string, costs: string, costsNote: string} */
    public function summary(): array
    {
        $acquisition = $this->possession->acquisitionTransaction();

        if (! $acquisition instanceof PossessionTransaction) {
            return [
                'acquired' => __('Unknown'),
                'how' => __('No acquisition recorded yet'),
                'price' => '—',
                'priceNote' => __('Add a purchase, an exchange, a gift or an inheritance'),
                'costs' => '—',
                'costsNote' => '',
            ];
        }

        $how = __($acquisition->type->label());

        if ($acquisition->counterparty !== null) {
            $how = implode(__(', '), [$how, $acquisition->counterparty]);
        }

        return [
            'acquired' => $acquisition->occurred_on === null ? __('Date unknown') : $this->writeDate($acquisition->occurred_on),
            'how' => $how,
            'price' => $this->money($acquisition->total(), $acquisition->currency),
            'priceNote' => __('From the first acquisition transaction'),
            ...$this->costsSince($acquisition),
        ];
    }

    /** @return array{ownership: int, all: int} */
    public function counts(): array
    {
        return [
            'ownership' => $this->possession->transactions->filter(fn (PossessionTransaction $transaction): bool => $transaction->type->changesOwnership())->count(),
            'all' => $this->possession->transactions->count(),
        ];
    }

    /**
     * Every transaction, newest first. An undated one is read as the oldest, as it is when the
     * acquisition is chosen, so it comes last.
     *
     * @return list<array{id: string, type: string, icon: string, changesOwnership: bool, isAcquisition: bool, date: string, counterparty: ?string, total: string, totalHow: string, parts: list<array{label: string, value: string, isSet: bool}>, note: ?string, reference: ?string, sourceUrl: ?string, documents: list<array{name: string, meta: string, url: string, destroyUrl: string}>, attachUrl: string, stamp: string, editUrl: string, destroyUrl: string, deleteWarning: string}>
     */
    public function transactions(): array
    {
        $acquisition = $this->possession->acquisitionTransaction();

        return $this->possession->transactionsOldestFirst()
            ->reverse()
            ->reject(fn (PossessionTransaction $transaction): bool => $this->editing?->is($transaction) ?? false)
            ->map(fn (PossessionTransaction $transaction): array => [
                'id' => $transaction->uuid,
                'type' => __($transaction->type->label()),
                'icon' => $transaction->type->icon(),
                'changesOwnership' => $transaction->type->changesOwnership(),
                'isAcquisition' => $acquisition?->is($transaction) ?? false,
                'date' => $transaction->occurred_on === null ? __('Date unknown') : $this->writeDate($transaction->occurred_on),
                'counterparty' => $transaction->counterparty,
                'total' => $transaction->total() === null ? __('Unknown') : $this->money($transaction->total(), $transaction->currency),
                'totalHow' => $this->totalHow($transaction),
                'parts' => [
                    $this->part(__('Amount'), $transaction->amount, $transaction->currency),
                    $this->part(__('Tax'), $transaction->tax, $transaction->currency),
                    $this->part(__('Fees'), $transaction->fees, $transaction->currency),
                    $this->part(__('Shipping'), $transaction->shipping, $transaction->currency),
                ],
                'note' => $transaction->note,
                'reference' => $transaction->reference,
                'sourceUrl' => $transaction->source_url,
                'documents' => $transaction->documents->map(fn (File $file): array => [
                    'name' => $file->original_name,
                    'meta' => __(':kind, :size', ['kind' => mb_strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION) ?: $file->mime_type), 'size' => Number::fileSize($file->size_in_bytes)]),
                    'url' => route('files.show', [$this->possession->vault, $file]),
                    'destroyUrl' => route('possessions.transactions.documents.destroy', [$this->possession->vault, $this->possession, $transaction, $file]),
                ])->values()->all(),
                'attachUrl' => route('possessions.transactions.documents.create', [$this->possession->vault, $this->possession, $transaction]),
                'stamp' => $this->stamp($transaction),
                'editUrl' => route('possessions.transactions.edit', [$this->possession->vault, $this->possession, $transaction]),
                'destroyUrl' => route('possessions.transactions.destroy', [$this->possession->vault, $this->possession, $transaction]),
                'deleteWarning' => $this->deleteWarning($transaction),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{label: string, types: list<array{value: string, label: string}>}> */
    public function types(): array
    {
        $types = collect(PossessionTransactionTypeEnum::cases());
        $option = fn (PossessionTransactionTypeEnum $type): array => ['value' => $type->value, 'label' => __($type->label())];

        return [
            ['label' => __('Change of ownership'), 'types' => $types->filter(fn (PossessionTransactionTypeEnum $type): bool => $type->changesOwnership())->map($option)->values()->all()],
            ['label' => __('Money only'), 'types' => $types->reject(fn (PossessionTransactionTypeEnum $type): bool => $type->changesOwnership())->map($option)->values()->all()],
        ];
    }

    /** @return array<string, string> */
    public function counterpartyLabels(): array
    {
        return collect(PossessionTransactionTypeEnum::cases())
            ->mapWithKeys(fn (PossessionTransactionTypeEnum $type): array => [$type->value => __($type->counterpartyLabel())])
            ->all();
    }

    /**
     * The currency a new transaction starts in: the one of the latest transaction, else the one of
     * the declared value, else none, so a currency is never guessed from the language.
     */
    public function preselectedCurrency(): ?CurrencyEnum
    {
        $latest = $this->possession->transactions
            ->filter(fn (PossessionTransaction $transaction): bool => $transaction->currency !== null)
            ->sortByDesc('id')
            ->first();

        return $latest->currency ?? CurrencyEnum::tryFrom((string) $this->possession->declared_value_currency);
    }

    /**
     * What the form starts with: the transaction being edited, else what was typed before a refusal,
     * else a purchase in the currency last used.
     *
     * @return array{isEditing: bool, action: string, method: string, cancelUrl: ?string, values: array<string, string>, currency: ?CurrencyEnum}
     */
    public function form(): array
    {
        $editing = $this->editing;

        if (! $editing instanceof PossessionTransaction) {
            return [
                'isEditing' => false,
                'action' => route('possessions.transactions.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'values' => [],
                'currency' => $this->preselectedCurrency(),
            ];
        }

        return [
            'isEditing' => true,
            'action' => route('possessions.transactions.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.transactions.index', [$this->possession->vault, $this->possession]),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'type' => $editing->type->value,
                'occurred_on' => $editing->occurred_on?->toDateString(),
                'counterparty' => $editing->counterparty,
                'amount' => $editing->amount,
                'tax' => $editing->tax,
                'fees' => $editing->fees,
                'shipping' => $editing->shipping,
                'entered_total' => $editing->entered_total,
                'reference' => $editing->reference,
                'source_url' => $editing->source_url,
                'note' => $editing->note,
            ]),
            'currency' => $editing->currency,
        ];
    }

    /**
     * What deleting a transaction takes with it, said before it happens: which transaction becomes
     * the acquisition when this one was it, and the documents that go too.
     */
    public function deleteWarning(PossessionTransaction $transaction): string
    {
        $sentences = [];

        if ($this->possession->acquisitionTransaction()?->is($transaction)) {
            $next = $this->possession->transactionsOldestFirst()
                ->first(fn (PossessionTransaction $other): bool => $other->type->isAcquisition() && ! $other->is($transaction));

            $sentences[] = match (true) {
                ! $next instanceof PossessionTransaction => __('The possession will no longer have an acquisition date or price.'),
                $next->occurred_on === null => __('An undated transaction will then be the acquisition.'),
                default => __('The transaction of :date will then be the acquisition.', ['date' => $this->writeDate($next->occurred_on)]),
            };
        }

        $documents = $transaction->documents->count();

        $sentences[] = $documents === 0
            ? __('This cannot be undone.')
            : trans_choice(':count attached document goes with it. This cannot be undone.|:count attached documents go with it. This cannot be undone.', $documents);

        return implode(' ', $sentences);
    }

    /** @return array{costs: string, costsNote: string} */
    private function costsSince(PossessionTransaction $acquisition): array
    {
        $since = $this->possession->transactions->filter(function (PossessionTransaction $transaction) use ($acquisition): bool {
            if ($transaction->type->changesOwnership() || $transaction->total() === null) {
                return false;
            }

            if ($acquisition->occurred_on === null) {
                return true;
            }

            return $transaction->occurred_on !== null && $transaction->occurred_on->gte($acquisition->occurred_on);
        });

        if ($since->isEmpty()) {
            return ['costs' => '—', 'costsNote' => __('No fee, tax or shipping recorded since')];
        }

        $currency = $acquisition->currency ?? $since->sortBy('occurred_on')->first()->currency;
        $counted = $since->filter(fn (PossessionTransaction $transaction): bool => $transaction->currency === $currency);
        $leftOut = $since->count() - $counted->count();

        $sum = $counted->reduce(
            fn (string $sum, PossessionTransaction $transaction): string => $transaction->type === PossessionTransactionTypeEnum::Refund
                ? bcsub($sum, (string) $transaction->total(), 2)
                : bcadd($sum, (string) $transaction->total(), 2),
            '0.00',
        );

        $note = trans_choice(':count fee, tax or shipping, in :currency|:count fees, taxes and shipping, in :currency', $counted->count(), [
            'currency' => $currency?->code() ?? '',
        ]);

        if ($leftOut > 0) {
            $note = implode(__(', '), [$note, trans_choice(':count in another currency left out|:count in other currencies left out', $leftOut)]);
        }

        return ['costs' => $this->money($sum, $currency), 'costsNote' => $note];
    }

    private function totalHow(PossessionTransaction $transaction): string
    {
        if ($transaction->totalSource() === 'entered') {
            return __('Total entered');
        }

        $figures = array_keys($transaction->figuresSet());

        if ($figures === []) {
            return __('No amount recorded');
        }

        if (count($figures) > 1) {
            return __('Sum of the figures entered');
        }

        return match ($figures[0]) {
            'amount' => __('From the amount'),
            'tax' => __('From the tax'),
            'fees' => __('From the fees'),
            default => __('From the shipping'),
        };
    }

    /** @return array{label: string, value: string, isSet: bool} */
    private function part(string $label, ?string $figure, ?CurrencyEnum $currency): array
    {
        return ['label' => $label, 'value' => $this->money($figure, $currency), 'isSet' => $figure !== null];
    }

    private function money(?string $amount, ?CurrencyEnum $currency): string
    {
        return new PossessionValueFormatter($this->user)->formatExact($amount, $currency?->value);
    }

    private function stamp(PossessionTransaction $transaction): string
    {
        $added = __('Added on :date', ['date' => $this->writeDate($transaction->created_at)]);

        if ($transaction->updated_at === null || $transaction->updated_at->equalTo($transaction->created_at)) {
            return $added;
        }

        return __(':added, last edited :when', ['added' => $added, 'when' => $transaction->updated_at->diffForHumans()]);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
