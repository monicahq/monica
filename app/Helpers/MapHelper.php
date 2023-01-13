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

        if (! is_null($address->line_1)) {
            $sentence = $address->line_1;
        }

        if (! is_null($address->line_2)) {
            $sentence .= ' '.$address->line_2;
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

    /**
     * Return the URL for a static image for the given place.
     *
     * @param  Address  $address
     * @param  int  $width
     * @param  int  $height
     * @param  int  $zoom
     * @return string|null
     */
    public static function getStaticImage(Address $address, int $width, int $height, int $zoom = 7): ?string
    {
        if (! config('monica.mapbox_api_key')) {
            return null;
        }

        if (! config('monica.mapbox_username')) {
            return null;
        }

        $url = 'https://api.mapbox.com/styles/v1/';
        $url .= config('monica.mapbox_username').'/';
        $url .= config('monica.mapbox_custom_style_name').'/static/';
        $url .= $address->longitude.',';
        $url .= $address->latitude.',';
        $url .= $zoom.'/';
        $url .= $width.'x'.$height;
        $url .= '?access_token='.config('monica.mapbox_api_key');

        return $url;
    }
}
