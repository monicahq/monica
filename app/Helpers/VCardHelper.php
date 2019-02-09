<?php

namespace App\Helpers;

use Sabre\VObject\Component\VCard;

class VCardHelper
{
    /**
     * Get country model object from given VCard file.
     *
     * @param VCard $VCard
     * @return string|null
     */
    public static function getCountryISOFromSabreVCard(VCard $VCard)
    {
        $VCardAddress = $VCard->ADR;

        if (empty($VCardAddress)) {
            return;
        }

        if (count($VCardAddress->getParts()) >= 7) {
            return CountriesHelper::find($VCardAddress->getParts()[6]);
        }
    }
}
