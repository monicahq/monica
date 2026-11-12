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

    public function code(): string
    {
        return mb_strtoupper($this->value);
    }

    /**
     * The flag of the country or the union issuing the currency, drawn as a CSS background the way
     * the locale flags in config/localization.php are, so no image is shipped.
     */
    public function flag(): string
    {
        return match ($this) {
            self::UsDollar => 'linear-gradient(#3c3b6e 0 0) 0 0 / 40% 54% no-repeat, repeating-linear-gradient(to bottom, #b22234 0 7.69%, #fff 7.69% 15.38%)',
            self::Euro => 'radial-gradient(circle at 50% 50%, transparent 0 26%, #ffcc00 26% 34%, transparent 34%), #003399',
            self::PoundSterling => 'linear-gradient(to bottom, transparent 40%, #C8102E 40% 60%, transparent 60%), linear-gradient(to right, transparent 38%, #C8102E 38% 62%, transparent 62%), linear-gradient(to bottom, transparent 30%, #fff 30% 70%, transparent 70%), linear-gradient(to right, transparent 28%, #fff 28% 72%, transparent 72%), linear-gradient(to bottom right, transparent 44%, #fff 44% 56%, transparent 56%), linear-gradient(to bottom left, transparent 44%, #fff 44% 56%, transparent 56%), #012169',
            self::CanadianDollar => 'radial-gradient(circle at 50% 50%, #d52b1e 0 16%, transparent 16%), linear-gradient(to right, #d52b1e 0 25%, #fff 25% 75%, #d52b1e 75%)',
            self::AustralianDollar => 'linear-gradient(to bottom, transparent 20%, #fff 20% 30%, transparent 30%) 0 0 / 50% 50% no-repeat, linear-gradient(to right, transparent 45%, #fff 45% 55%, transparent 55%) 0 0 / 50% 50% no-repeat, radial-gradient(circle at 75% 60%, #fff 0 5%, transparent 5%), #00008b',
            self::SwissFranc => 'linear-gradient(to bottom, transparent 40%, #fff 40% 60%, transparent 60%) 50% 50% / 60% 100% no-repeat, linear-gradient(to right, transparent 42%, #fff 42% 58%, transparent 58%) 50% 50% / 100% 64% no-repeat, #da291c',
            self::JapaneseYen => 'radial-gradient(circle at 50% 50%, #bc002d 0 35%, #fff 35%)',
            self::BrazilianReal => 'radial-gradient(circle at 50% 50%, #002776 0 22%, transparent 22%), linear-gradient(to bottom right, transparent 35%, #ffdf00 35% 65%, transparent 65%), linear-gradient(to bottom left, transparent 35%, #ffdf00 35% 65%, transparent 65%), #009c3b',
            self::IndianRupee => 'radial-gradient(circle at 50% 50%, transparent 0 5%, #000080 5% 7%, transparent 7%), linear-gradient(to bottom, #ff9933 0 33.33%, #fff 33.33% 66.66%, #138808 66.66%)',
            self::ChineseYuan => 'radial-gradient(circle at 20% 32%, #ffde00 0 22%, transparent 22%), radial-gradient(circle at 40% 15%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 49% 30%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 46% 49%, #ffde00 0 7%, transparent 7%), radial-gradient(circle at 35% 61%, #ffde00 0 7%, transparent 7%), #de2910',
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
