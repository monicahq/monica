<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

/**
 * The currencies somebody is most likely to write an amount in.
 *
 * The name carries its ISO 4217 code because half of them are called dollar,
 * and a list offering three of those with nothing to tell them apart is worse
 * than no list. The code is in the key as well, where the product reads it.
 */
enum CurrencyEnum: string implements SuppliedValueListItem
{
    case UsDollar = 'usd';
    case Euro = 'eur';
    case PoundSterling = 'gbp';
    case CanadianDollar = 'cad';
    case AustralianDollar = 'aud';
    case SwissFranc = 'chf';
    case JapaneseYen = 'jpy';
    case BrazilianReal = 'brl';
    case IndianRupee = 'inr';
    case ChineseYuan = 'cny';

    /**
     * The name of the currency and its code, which doubles as the translation
     * key.
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::UsDollar => 'US dollar (USD)',
            self::Euro => 'Euro (EUR)',
            self::PoundSterling => 'Pound sterling (GBP)',
            self::CanadianDollar => 'Canadian dollar (CAD)',
            self::AustralianDollar => 'Australian dollar (AUD)',
            self::SwissFranc => 'Swiss franc (CHF)',
            self::JapaneseYen => 'Japanese yen (JPY)',
            self::BrazilianReal => 'Brazilian real (BRL)',
            self::IndianRupee => 'Indian rupee (INR)',
            self::ChineseYuan => 'Chinese yuan (CNY)',
        };
    }

    /**
     * The name this item is seeded with.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
