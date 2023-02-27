<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Str;

class MonetaryNumberHelper
{
    /**
     * Format the number according to the user preferences.
     * We don't use the number_format PHP function because it rounds the number.
     * We also don't use the Money library because it requires the intl PHP
     * extension and we want to keep the software as far away as dependencies
     * as possible.
     */
    public static function format(User $user, int $number): string
    {
        $formattedNumber = '';
        $numberAsString = (string) $number;
        $length = Str::length($numberAsString);
        $thousands = Str::substr($numberAsString, 0, $length - 2);
        $lengthThousands = Str::length($thousands);
        $decimals = Str::substr($numberAsString, $length - 2, 2);

        switch ($user->number_format) {
            // 1,234.56
            case User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL:
                for ($i = 0; $i < $lengthThousands; $i++) {
                    if (($i % 3 == 0) && $i) {
                        $formattedNumber = ','.$formattedNumber;
                    }
                    $formattedNumber = $thousands[$lengthThousands - $i - 1].$formattedNumber;
                }
                $formattedNumber = $formattedNumber.'.'.$decimals;
                break;

                // 1 234,56
            case User::NUMBER_FORMAT_TYPE_SPACE_THOUSANDS_COMMA_DECIMAL:
                for ($i = 0; $i < $lengthThousands; $i++) {
                    if (($i % 3 == 0) && $i) {
                        $formattedNumber = ' '.$formattedNumber;
                    }
                    $formattedNumber = $thousands[$lengthThousands - $i - 1].$formattedNumber;
                }
                $formattedNumber = $formattedNumber.','.$decimals;
                break;

                // 1234.56
            case User::NUMBER_FORMAT_TYPE_NO_SPACE_DOT_DECIMAL:
                for ($i = 0; $i < $lengthThousands; $i++) {
                    $formattedNumber = $thousands[$lengthThousands - $i - 1].$formattedNumber;
                }
                $formattedNumber = $formattedNumber.'.'.$decimals;
                break;

            default:
                $formattedNumber = '';
                break;
        }

        return $formattedNumber;
    }
}
