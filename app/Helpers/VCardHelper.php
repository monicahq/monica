<?php

namespace App\Helpers;

use Sabre\VObject\Component\VCard;

class VCardHelper
{
    /**
     * Get country model object from given VCard file.
     *
     * @param VCard $vCard
     * @return string|null
     */
    public static function getCountryISOFromSabreVCard(VCard $vCard)
    {
        $vCardAddress = $vCard->ADR;

        if (empty($vCardAddress)) {
            return;
        }

        $country = array_get($vCardAddress->getParts(), '6');
        if (! empty($country)) {
            return CountriesHelper::find($country);
        }
    }
}
