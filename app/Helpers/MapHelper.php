<?php

namespace App\Helpers;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Str;

class MapHelper
{
    /**
     * Return a link for the given address, on the website based on the
     * preferences of the given user.
     *
     * @param  Address  $address
     * @param  User  $user
     * @return string
     */
    public static function getMapLink(Address $address, User $user): string
    {
        $addressAsString = self::getAddressAsString($address);
        if (! $addressAsString) {
            return '';
        }

        $encodedAddress = urlencode($addressAsString);
        $url = '';

        if ($user->default_map_site == User::MAPS_SITE_GOOGLE_MAPS) {
            $url = "https://www.google.com/maps/place/{$encodedAddress}";
        }

        if ($user->default_map_site == User::MAPS_SITE_OPEN_STREET_MAPS) {
            $url = "https://www.openstreetmap.org/search?query={$encodedAddress}";
        }

        return $url;
    }

    /**
     * Get the address as a sentence.
     *
     * @return string|null
     */
    public static function getAddressAsString(Address $address): ?string
    {
        $sentence = '';

        if (! is_null($address->street)) {
            $sentence = $address->street;
        }

        if (! is_null($address->city)) {
            $sentence .= ' '.$address->city;
        }

        if (! is_null($address->province)) {
            $sentence .= ' '.$address->province;
        }

        if (! is_null($address->postal_code)) {
            $sentence .= ' '.$address->postal_code;
        }

        if (! is_null($address->country)) {
            $sentence .= ' '.$address->country;
        }

        // trim extra whitespaces inside the address
        return Str::of($sentence)->replaceMatches('/\s+/', ' ');
    }
}
