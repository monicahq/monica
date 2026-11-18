<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Cashier;

class BillingStatusViewModel
{
    /**
     * @param array{
     *     connection: string,
     *     endpointsReadable: bool,
     *     endpoints: list<array{url: ?string, isThisInstance: bool, isEnabled: bool}>,
     *     missingEvents: list<string>
     * }|null $connection
     */
    public function __construct(
        private readonly ?array $connection,
    ) {}

    public function isSelfHosted(): bool
    {
        return (bool) config('monica.self_hosted');
    }

    public function isReachable(): bool
    {
        return ($this->connection['connection'] ?? null) === 'reachable';
    }

    public function isCatalogueAvailable(): bool
    {
        return StripeCatalogue::isAvailable();
    }

    public function catalogueReadAt(): ?CarbonImmutable
    {
        return StripeCatalogue::readAt();
    }

    /** @return list<array{kind: string, amounts: list<string>}> */
    public function offers(): array
    {
        return array_map(fn (array $offer): array => [
            'kind' => __($offer['kind']->label()),
            'amounts' => array_values(array_map(
                fn (string $currency, int $amount): string => Cashier::formatAmount($amount, $currency, app()->getLocale()),
                array_keys($offer['prices']),
                $offer['prices'],
            )),
        ], StripeCatalogue::offers());
    }

    /** @return list<string> */
    public function kindsWithoutPrice(): array
    {
        $kindsOnSale = array_map(fn (array $offer): OfferKindEnum => $offer['kind'], StripeCatalogue::offers());

        return array_values(array_map(
            fn (OfferKindEnum $kind): string => __($kind->label()),
            array_filter(OfferKindEnum::cases(), fn (OfferKindEnum $kind): bool => ! in_array($kind, $kindsOnSale, true)),
        ));
    }

    public function endpointsAreReadable(): bool
    {
        return $this->connection['endpointsReadable'] ?? false;
    }

    /** @return list<array{address: string, isEnabled: bool, state: string}> */
    public function endpoints(): array
    {
        $endpoints = [];

        foreach ($this->connection['endpoints'] ?? [] as $endpoint) {
            if (! $endpoint['isThisInstance']) {
                continue;
            }

            $endpoints[] = [
                'address' => (string) $endpoint['url'],
                'isEnabled' => $endpoint['isEnabled'],
                'state' => $endpoint['isEnabled'] ? __('Enabled') : __('Disabled'),
            ];
        }

        return $endpoints;
    }

    public function hasEnabledEndpoint(): bool
    {
        foreach ($this->connection['endpoints'] ?? [] as $endpoint) {
            if ($endpoint['isThisInstance'] && $endpoint['isEnabled']) {
                return true;
            }
        }

        return false;
    }

    public function endpointsElsewhere(): ?string
    {
        $count = count(array_filter($this->connection['endpoints'] ?? [], fn (array $endpoint): bool => ! $endpoint['isThisInstance']));

        if ($count === 0) {
            return null;
        }

        return trans_choice(':count endpoint points at another address and is not counted.|:count endpoints point at another address and are not counted.', $count);
    }

    /** @return list<array{event: string, consequence: string}> */
    public function missingEvents(): array
    {
        return array_map(fn (string $event): array => [
            'event' => $event,
            'consequence' => match ($event) {
                'checkout.session.completed' => __('A buyer who pays once is charged and left on the free offer.'),
                'checkout.session.async_payment_succeeded' => __('A buyer paying by bank transfer or debit is charged and left on the free offer.'),
                'invoice.paid' => __('No payment is written down, so no screen shows what was charged.'),
                'charge.refunded' => __('A full refund leaves access open.'),
                'charge.refund.updated' => __('A refund that fails or is called off does not put access back.'),
                'charge.dispute.created' => __('An open dispute leaves access open.'),
                'charge.dispute.closed' => __('A dispute won does not put access back.'),
                'customer.subscription.created' => __('A new subscription is not recorded and access does not open.'),
                'customer.subscription.updated' => __('A subscription falls out of step: its state, its price and its end date.'),
                'customer.subscription.deleted' => __('Access does not end when a subscription ends.'),
                'customer.updated' => __('The card shown to its owner goes out of date.'),
                'customer.deleted' => __('The subscriptions of a deleted customer are not cancelled.'),
                'payment_method.automatically_updated' => __('A card the network replaces goes unseen.'),
                'invoice.payment_succeeded' => __('The bookkeeping on a subscription opened through a checkout is skipped.'),
                default => __('Nothing this product shows depends on it.'),
            },
        ], $this->connection['missingEvents'] ?? []);
    }

    public function events(): StripeEvents
    {
        return new StripeEvents;
    }

    public function figures(): BillingFigures
    {
        return new BillingFigures;
    }

    public function monthLabel(CarbonImmutable $month): string
    {
        return $month->locale(app()->getLocale())->isoFormat('MMMM YYYY');
    }

    public function offerLabel(?OfferKindEnum $kind): string
    {
        return $kind === null ? __('Kind not known') : __($kind->label());
    }

    public function amount(int $amount, string $currency): string
    {
        return Cashier::formatAmount($amount, $currency, app()->getLocale());
    }
}
