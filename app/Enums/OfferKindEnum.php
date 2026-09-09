<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum OfferKindEnum: string implements Translatable
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly subscription',
            self::Yearly => 'Yearly subscription',
            self::Lifetime => 'Lifetime access',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Monthly => 'Renews every month until you cancel it.',
            self::Yearly => 'Renews every year until you cancel it.',
            self::Lifetime => 'Pay once and keep full access for good.',
        };
    }

    public function callToAction(): string
    {
        return match ($this) {
            self::Monthly => 'Subscribe monthly',
            self::Yearly => 'Subscribe yearly',
            self::Lifetime => 'Buy lifetime access',
        };
    }

    /**
     * How a price of this kind is written around the amount the provider quotes.
     */
    public function priceFormat(): string
    {
        return match ($this) {
            self::Monthly => ':price/month',
            self::Yearly => ':price/year',
            self::Lifetime => ':price once',
        };
    }

    public function renews(): bool
    {
        return $this !== self::Lifetime;
    }

    /**
     * Whether this offer sits above another: the cases are declared from the shortest commitment to
     * the longest, and a subscriber may only move up.
     */
    public function isAbove(self $other): bool
    {
        return array_search($this, self::cases(), true) > array_search($other, self::cases(), true);
    }

    /**
     * The billing interval the provider's price has to carry for this kind, null for a price paid once.
     */
    public function billingInterval(): ?string
    {
        return match ($this) {
            self::Monthly => 'month',
            self::Yearly => 'year',
            self::Lifetime => null,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label(), $this->description(), $this->callToAction(), $this->priceFormat()];
    }
}
