<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\SuppliedValueListItem;
use Override;

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

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
