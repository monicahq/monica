<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\PossessionValueFormatter;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The Provenance section of a possession: where it comes from, who has held it and how that is known,
 * oldest first. The origin, the chain of ownership and what is verified are worked out from the events
 * each time. What a moment cost is read from the transaction it points at.
 */
class PossessionProvenanceViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionProvenanceEvent $editing = null,
    ) {
        $this->possession->load(['provenanceEvents.transaction', 'transactions']);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    /** @return array{origin: string, originNote: string, chain: string, chainNote: string, verified: string, verifiedNote: string} */
    public function summary(): array
    {
        $events = $this->possession->provenanceEvents;
        $origin = $events->first(fn (PossessionProvenanceEvent $event): bool => $event->type === PossessionProvenanceEventTypeEnum::Origin);
        $owners = $this->owners();
        $unverified = $events->reject(fn (PossessionProvenanceEvent $event): bool => $event->is_verified)->count();

        return [
            'origin' => match (true) {
                ! $origin instanceof PossessionProvenanceEvent => __('Unknown'),
                $origin->location === null => __('Place unknown'),
                default => $origin->location,
            },
            'originNote' => match (true) {
                ! $origin instanceof PossessionProvenanceEvent => __('No origin event yet'),
                $origin->is_verified => __(':date, verified', ['date' => $this->writeEventDate($origin)]),
                default => __(':date, not verified', ['date' => $this->writeEventDate($origin)]),
            },
            'chain' => $owners === []
                ? __('No owner recorded')
                : trans_choice(':count owner known|:count owners known', count($owners)),
            'chainNote' => $owners === []
                ? __('Add an acquisition, a gift or an inheritance')
                : implode(__(', then '), $owners),
            'verified' => $events->isEmpty()
                ? '—'
                : trans_choice(':verified of :count event|:verified of :count events', $events->count(), ['verified' => $events->count() - $unverified]),
            'verifiedNote' => $unverified === 0
                ? __('Every event is backed by a record')
                : trans_choice(':count event rests on memory alone|:count events rest on memory alone', $unverified),
        ];
    }

    /** @return array{all: int, ownership: int, custody: int, authenticity: int} */
    public function counts(): array
    {
        $events = $this->possession->provenanceEvents;
        $inGroup = fn (string $group): int => $events->filter(fn (PossessionProvenanceEvent $event): bool => $event->type->group() === $group)->count();

        return [
            'all' => $events->count(),
            'ownership' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_OWNERSHIP),
            'custody' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_CUSTODY),
            'authenticity' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_AUTHENTICITY),
        ];
    }

    /**
     * Every event, oldest first, those with an unknown date last. The one being edited is left out,
     * since the form stands in its place.
     *
     * @return list<array{id: string, group: string, type: string, icon: string, date: string, title: string, isVerified: bool, who: ?string, description: ?string, reference: ?string, sourceUrl: ?string, transaction: ?array{label: string, total: string, url: string}, editUrl: string, destroyUrl: string, deleteTitle: string, deleteWarning: string}>
     */
    public function events(): array
    {
        return $this->possession->provenanceEvents
            ->reject(fn (PossessionProvenanceEvent $event): bool => $this->editing?->is($event) ?? false)
            ->map(fn (PossessionProvenanceEvent $event): array => [
                'id' => $event->uuid,
                'group' => $event->type->group(),
                'type' => __($event->type->label()),
                'icon' => $event->type->icon(),
                'date' => $this->writeEventDate($event),
                'title' => $this->title($event),
                'isVerified' => $event->is_verified,
                'who' => $this->who($event),
                'description' => $event->description,
                'reference' => $event->reference_number,
                'sourceUrl' => $event->source_url,
                'transaction' => $event->transaction instanceof PossessionTransaction ? [
                    'label' => $this->transactionLink($event->transaction),
                    'total' => $this->money($event->transaction) ?? __('No amount recorded'),
                    'url' => route('possessions.transactions.index', [$this->possession->vault, $this->possession]),
                ] : null,
                'editUrl' => route('possessions.provenance.edit', [$this->possession->vault, $this->possession, $event]),
                'destroyUrl' => route('possessions.provenance.destroy', [$this->possession->vault, $this->possession, $event]),
                'deleteTitle' => __('Delete “:title”?', ['title' => $this->title($event)]),
                'deleteWarning' => $event->transaction instanceof PossessionTransaction
                    ? __('It is removed from the provenance of this possession, and this cannot be undone. The linked transaction stays in Transactions.')
                    : __('It is removed from the provenance of this possession, and this cannot be undone.'),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{label: string, types: list<array{value: string, label: string}>}> */
    public function types(): array
    {
        $types = collect(PossessionProvenanceEventTypeEnum::cases());
        $inGroup = fn (string $group): array => $types
            ->filter(fn (PossessionProvenanceEventTypeEnum $type): bool => $type->group() === $group)
            ->map(fn (PossessionProvenanceEventTypeEnum $type): array => ['value' => $type->value, 'label' => __($type->label())])
            ->values()
            ->all();

        return [
            ['label' => __('Ownership'), 'types' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_OWNERSHIP)],
            ['label' => __('Custody'), 'types' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_CUSTODY)],
            ['label' => __('Authenticity and record'), 'types' => $inGroup(PossessionProvenanceEventTypeEnum::GROUP_AUTHENTICITY)],
        ];
    }

    /** @return array<string, string> */
    public function titlePlaceholders(): array
    {
        return collect(PossessionProvenanceEventTypeEnum::cases())
            ->mapWithKeys(fn (PossessionProvenanceEventTypeEnum $type): array => [$type->value => __($type->titlePlaceholder())])
            ->all();
    }

    /** @return list<array{value: string, label: string, help: string}> */
    public function precisions(): array
    {
        return array_map(fn (PossessionProvenanceDatePrecisionEnum $precision): array => [
            'value' => $precision->value,
            'label' => __($precision->label()),
            'help' => __($precision->help()),
        ], PossessionProvenanceDatePrecisionEnum::cases());
    }

    /**
     * The transactions an event may point at, oldest first, each named by its type, date, counterparty
     * and total.
     *
     * @return list<array{value: string, label: string}>
     */
    public function transactionOptions(): array
    {
        return $this->possession->transactionsOldestFirst()
            ->map(fn (PossessionTransaction $transaction): array => [
                'value' => $transaction->uuid,
                'label' => implode(' · ', array_filter([
                    implode(__(', '), array_filter([
                        __($transaction->type->label()),
                        $transaction->occurred_on === null ? __('Date unknown') : $this->writeDate($transaction->occurred_on),
                        $transaction->counterparty,
                    ])),
                    $this->money($transaction),
                ])),
            ])
            ->values()
            ->all();
    }

    /**
     * What the form starts with: the event being edited, else what was typed before a refusal, else
     * an acquisition on an exact date.
     *
     * @return array{isEditing: bool, action: string, method: string, cancelUrl: ?string, values: array<string, string>, isVerified: bool}
     */
    public function form(): array
    {
        $editing = $this->editing;

        if (! $editing instanceof PossessionProvenanceEvent) {
            return [
                'isEditing' => false,
                'action' => route('possessions.provenance.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'values' => [],
                'isVerified' => false,
            ];
        }

        return [
            'isEditing' => true,
            'action' => route('possessions.provenance.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.provenance.index', [$this->possession->vault, $this->possession]),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'type' => $editing->type->value,
                'title' => $editing->title,
                'date_precision' => $editing->date_precision->value,
                'occurred_on' => $editing->date_precision->format($editing->occurred_on),
                'from' => $editing->from,
                'to' => $editing->to,
                'location' => $editing->location,
                'description' => $editing->description,
                'reference_number' => $editing->reference_number,
                'source_url' => $editing->source_url,
                'transaction' => $editing->transaction?->uuid,
            ]),
            'isVerified' => $editing->is_verified,
        ];
    }

    /**
     * The people written in "to" on the ownership events, in the order of the chain, each once.
     *
     * @return list<string>
     */
    private function owners(): array
    {
        return $this->possession->provenanceEvents
            ->filter(fn (PossessionProvenanceEvent $event): bool => $event->type->group() === PossessionProvenanceEventTypeEnum::GROUP_OWNERSHIP && $event->to !== null)
            ->map(fn (PossessionProvenanceEvent $event): string => mb_trim((string) $event->to))
            ->unique()
            ->values()
            ->all();
    }

    private function title(PossessionProvenanceEvent $event): string
    {
        return $event->title ?? __($event->type->label());
    }

    private function who(PossessionProvenanceEvent $event): ?string
    {
        $parts = new Collection([
            'from' => $event->from,
            'to' => $event->to,
            'location' => $event->location,
        ])->filter(fn (?string $value): bool => $value !== null);

        if ($parts->isEmpty()) {
            return null;
        }

        return match (true) {
            $parts->has(['from', 'to', 'location']) => __('From :from to :to in :location.', $parts->all()),
            $parts->has(['from', 'to']) => __('From :from to :to.', $parts->all()),
            $parts->has(['from', 'location']) => __('From :from in :location.', $parts->all()),
            $parts->has(['to', 'location']) => __('To :to in :location.', $parts->all()),
            $parts->has('from') => __('From :from.', $parts->all()),
            $parts->has('to') => __('To :to.', $parts->all()),
            default => __('In :location.', $parts->all()),
        };
    }

    private function transactionLink(PossessionTransaction $transaction): string
    {
        $type = __($transaction->type->label());

        if ($transaction->occurred_on === null) {
            return __('Linked transaction: :type', ['type' => $type]);
        }

        return __('Linked transaction: :type, :date', ['type' => $type, 'date' => $this->writeDate($transaction->occurred_on)]);
    }

    private function writeEventDate(PossessionProvenanceEvent $event): string
    {
        $date = $event->occurred_on;

        if ($date === null) {
            return __('Date unknown');
        }

        return match ($event->date_precision) {
            PossessionProvenanceDatePrecisionEnum::Unknown => __('Date unknown'),
            PossessionProvenanceDatePrecisionEnum::Day => $this->writeDate($date),
            PossessionProvenanceDatePrecisionEnum::Month => $date->locale(app()->getLocale())->isoFormat('MMMM YYYY'),
            PossessionProvenanceDatePrecisionEnum::Year => $date->format('Y'),
            PossessionProvenanceDatePrecisionEnum::Circa => __('About :year', ['year' => $date->format('Y')]),
        };
    }

    private function money(PossessionTransaction $transaction): ?string
    {
        if ($transaction->total() === null) {
            return null;
        }

        return new PossessionValueFormatter($this->user)->formatExact($transaction->total(), $transaction->currency?->value);
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
