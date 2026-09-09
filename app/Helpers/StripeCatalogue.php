<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\OfferKindEnum;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Stripe\Price;
use Stripe\Product;

class StripeCatalogue
{
    private const string CACHE_KEY = 'stripe_offers';

    private const int CACHE_MINUTES = 60;

    private const int PRICES_PER_READ = 100;

    /**
     * The offers Stripe sells: every active price of a product this instance sells, carrying the
     * tag that names its kind, whose billing cycle agrees with that kind, in the order
     * the kinds are declared, each with the amount it quotes in the smallest unit of its currency,
     * and under `prices` every amount it quotes by currency, the base one included. Read once an
     * hour. Empty when the catalogue could not be read, which isAvailable() tells apart from
     * nothing being for sale.
     *
     * @return array<int, array{price_id: string, kind: OfferKindEnum, amount: int, currency: string, prices: array<string, int>}>
     */
    public static function offers(): array
    {
        return array_map(
            fn (array $offer): array => [
                'price_id' => $offer['price_id'],
                'kind' => OfferKindEnum::from($offer['kind']),
                'amount' => $offer['amount'],
                'currency' => $offer['currency'],
                'prices' => $offer['prices'],
            ],
            self::read() ?? [],
        );
    }

    /**
     * Every currency the catalogue quotes at least one offer in, alphabetically.
     *
     * @return list<string>
     */
    public static function currencies(): array
    {
        $currencies = [];

        foreach (self::offers() as $offer) {
            $currencies = [...$currencies, ...array_keys($offer['prices'])];
        }

        $currencies = array_values(array_unique($currencies));
        sort($currencies);

        return $currencies;
    }

    public static function isAvailable(): bool
    {
        return self::read() !== null;
    }

    public static function kindOf(string $priceId): ?OfferKindEnum
    {
        foreach (self::offers() as $offer) {
            if ($offer['price_id'] === $priceId) {
                return $offer['kind'];
            }
        }

        return null;
    }

    public static function kindOfSubscription(Subscription $subscription): ?OfferKindEnum
    {
        $priceId = $subscription->stripe_price ?? $subscription->items()->value('stripe_price');

        return $priceId === null ? null : self::kindOf((string) $priceId);
    }

    /**
     * Which offer a payment was for: the one time purchase when its invoice belongs to no
     * subscription, otherwise whatever the subscription it charged is on, null when nothing runs
     * any more and the catalogue no longer knows the price.
     */
    public static function kindOfPayment(Payment $payment): ?OfferKindEnum
    {
        if (! $payment->isForSubscription()) {
            return OfferKindEnum::Lifetime;
        }

        $subscription = Cashier::$subscriptionModel::query()
            ->where('stripe_id', $payment->stripe_subscription_id)
            ->first();

        return $subscription === null ? null : self::kindOfSubscription($subscription);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, array{price_id: string, kind: string, amount: int, currency: string, prices: array<string, int>}>|null */
    private static function read(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (self::isACatalogue($cached)) {
            return $cached;
        }

        try {
            $prices = Cashier::stripe()->prices->all([
                'active' => true,
                'limit' => self::PRICES_PER_READ,
                'expand' => ['data.currency_options', 'data.product'],
            ])->data;
        } catch (Exception $exception) {
            Log::warning('The Stripe price catalogue could not be read.', ['reason' => $exception->getMessage()]);

            return null;
        }

        $offers = self::offersAmong($prices);

        Cache::put(self::CACHE_KEY, $offers, now()->addMinutes(self::CACHE_MINUTES));

        return $offers;
    }

    /**
     * Whether a cached value is a catalogue in the shape this class writes. One left by an older
     * version is read again from Stripe rather than trusted, so a deploy never needs the cache
     * cleared.
     *
     * @phpstan-assert-if-true array<int, array{price_id: string, kind: string, amount: int, currency: string, prices: array<string, int>}> $cached
     */
    private static function isACatalogue(mixed $cached): bool
    {
        if (! is_array($cached)) {
            return false;
        }

        foreach ($cached as $offer) {
            if (! is_array($offer)) {
                return false;
            }

            if (array_diff(['price_id', 'kind', 'amount', 'currency', 'prices'], array_keys($offer)) !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  iterable<Price>  $prices
     * @return array<int, array{price_id: string, kind: string, amount: int, currency: string, prices: array<string, int>}>
     */
    private static function offersAmong(iterable $prices): array
    {
        $tagKey = (string) config('monica.offer_tag');
        $offers = [];

        foreach ($prices as $price) {
            if (! self::sellsTheProduct($price)) {
                continue;
            }

            $tag = $price->metadata[$tagKey] ?? null;

            if ($tag === null) {
                continue;
            }

            $kind = OfferKindEnum::tryFrom((string) $tag);

            if ($kind === null) {
                Log::warning('A Stripe price carries an offer tag the product does not know, so it is not for sale here.', ['price_id' => $price->id, 'tag' => $tag]);

                continue;
            }

            if (! self::billsLike($price, $kind)) {
                Log::warning('A Stripe price is tagged as one kind of offer but bills like another, so it is not for sale here.', ['price_id' => $price->id, 'tag' => $tag]);

                continue;
            }

            $currency = (string) ($price->currency ?? config('cashier.currency'));
            $amount = (int) ($price->unit_amount ?? 0);

            $offers[] = [
                'price_id' => (string) $price->id,
                'kind' => $kind->value,
                'amount' => $amount,
                'currency' => $currency,
                'prices' => self::pricesOf($price, $currency, $amount),
            ];
        }

        usort($offers, fn (array $a, array $b): int => self::position($a['kind']) <=> self::position($b['kind']));

        return $offers;
    }

    /**
     * Every amount a price quotes by currency: the base one, then whatever `currency_options`
     * adds, which is how Stripe prices the same offer in another country's money.
     *
     * @return array<string, int>
     */
    private static function pricesOf(Price $price, string $currency, int $amount): array
    {
        $prices = [$currency => $amount];

        foreach ($price->currency_options?->toArray() ?? [] as $code => $option) {
            $code = (string) $code;

            if (isset($prices[$code])) {
                continue;
            }

            $prices[$code] = (int) ($option['unit_amount'] ?? 0);
        }

        return $prices;
    }

    /**
     * Whether a price belongs to a product this instance sells: the one whose metadata carries the
     * key the configuration names, holding the value it names. A configuration naming no key sells
     * every product, which is what an instance selling its whole Stripe account wants. One naming a
     * key and no value asks only that the key is there, whatever it holds.
     */
    private static function sellsTheProduct(Price $price): bool
    {
        $key = config('monica.product_tag.key');

        if ($key === null || $key === '') {
            return true;
        }

        $product = $price->product;
        $tag = $product instanceof Product ? ($product->metadata[(string) $key] ?? null) : null;
        $value = config('monica.product_tag.value');

        if ($value === null || $value === '') {
            return $tag !== null;
        }

        return $tag === (string) $value;
    }

    private static function billsLike(Price $price, OfferKindEnum $kind): bool
    {
        if ($kind->billingInterval() === null) {
            return $price->type === 'one_time';
        }

        $recurring = $price->recurring;

        if ($recurring === null) {
            return false;
        }

        return $recurring->interval === $kind->billingInterval()
            && (int) ($recurring->interval_count ?? 1) === 1;
    }

    private static function position(string $kind): int
    {
        return (int) array_search(OfferKindEnum::from($kind), OfferKindEnum::cases(), true);
    }
}
