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
            self::Monthly => 'Billed monthly. Cancel renewal anytime.',
            self::Yearly => 'Billed yearly. Cancel renewal anytime.',
            self::Lifetime => 'Full access for the lifetime of the service. No recurring payments.',
        };
    }

    /**
     * The one line that says who the plan suits, above its description.
     */
    public function tagline(): string
    {
        return match ($this) {
            self::Monthly => 'Take it one month at a time.',
            self::Yearly => 'A year of room to grow.',
            self::Lifetime => 'Pay once. No subscription to manage.',
        };
    }

    public function callToAction(): string
    {
        return match ($this) {
            self::Monthly => 'Choose monthly',
            self::Yearly => 'Choose yearly',
            self::Lifetime => 'Get lifetime access',
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
        return [$this->label(), $this->description(), $this->tagline(), $this->callToAction(), $this->priceFormat()];
    }
}
