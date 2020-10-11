<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Sabre\VObject\Component\VCard;

class VCardHelper
{
    /**
     * Get country model object from given VCard file.
     *
     * @param VCard $vCard
     * @return string|null
     */
    public static function getCountryISOFromSabreVCard(VCard $vCard): ?string
    {
        $vCardAddress = $vCard->ADR;

        if (empty($vCardAddress)) {
            return null;
        }

        $country = Arr::get($vCardAddress->getParts(), '6');

        return empty($country) ? null : CountriesHelper::find($country);
    }
}
