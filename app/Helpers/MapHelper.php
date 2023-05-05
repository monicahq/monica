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
     */
    public static function getMapLink(Address $address, User $user): string
    {
        $addressAsString = self::getAddressAsString($address);
        if (! $addressAsString) {
            return '';
        }

        $encodedAddress = urlencode($addressAsString);

        return match ($user->default_map_site) {
            User::MAPS_SITE_GOOGLE_MAPS => "https://www.google.com/maps/place/{$encodedAddress}",
            User::MAPS_SITE_OPEN_STREET_MAPS => "https://www.openstreetmap.org/search?query={$encodedAddress}",
            default => "https://www.google.com/maps/place/{$encodedAddress}",
        };
    }

    /**
     * Get the address as a sentence.
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
