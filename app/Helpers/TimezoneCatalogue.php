<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeZone;
use Locale;

/**
 * Every time zone the product will accept, and where each one is.
 *
 * PHP is asked rather than told. `DateTimeZone::ALL` is the canonical list, so
 * the deprecated aliases and the `Etc/*` offsets are absent without anything
 * here excluding them, and every entry carries a country and a position already.
 * That is what lets a click on a map become a zone with no geographic data file
 * and no service to call.
 *
 * The set is exactly what the `timezone:all` validation rule accepts, which is
 * how a zone offered by a screen and a zone allowed by an endpoint stay one set
 * with nothing keeping them in step.
 */
class TimezoneCatalogue
{
    /**
     * The window the map is drawn in, from `specs/011-timezone-picker/research.md`.
     * Latitude 84 to -58 in a box a thousand wide, which is the aspect the
     * geometry has, so a position is a straight division either way.
     */
    public const float MAP_WIDTH = 1000.0;

    public const float MAP_HEIGHT = 394.44;

    public const float MAP_NORTH = 84.0;

    public const float MAP_SOUTH = -58.0;

    /** @var array<string, array<int, array{timezone: string, city: string, country: string, latitude: float, longitude: float}>> */
    private static array $zones = [];

    /**
     * Every zone, ordered by city, described in the language being read.
     *
     * @return array<int, array{timezone: string, city: string, country: string, latitude: float, longitude: float}>
     */
    public static function all(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if (array_key_exists($locale, self::$zones)) {
            return self::$zones[$locale];
        }

        $zones = [];

        foreach (timezone_identifiers_list(DateTimeZone::ALL) as $timezone) {
            $location = (new DateTimeZone($timezone))->getLocation();

            if ($location === false) {
                continue;
            }

            $zones[] = [
                'timezone' => $timezone,
                'city' => self::cityOf($timezone),
                'country' => self::countryOf($location['country_code'], $locale),
                'latitude' => (float) $location['latitude'],
                'longitude' => (float) $location['longitude'],
            ];
        }

        $collator = LocaleCollation::getCollator($locale);
        usort($zones, fn (array $a, array $b): int => (int) $collator->compare($a['city'], $b['city']));

        return self::$zones[$locale] = $zones;
    }

    /**
     * One zone, or null when the product does not know it. Asked by the screen
     * to describe what is stored, and by the button that offers whatever the
     * browser reports, which may be a zone this list does not carry.
     *
     * @return array{timezone: string, city: string, country: string, latitude: float, longitude: float}|null
     */
    public static function find(string $timezone, ?string $locale = null): ?array
    {
        foreach (self::all($locale) as $zone) {
            if ($zone['timezone'] === $timezone) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * The zone nearest to somewhere on the map, which is what a click means.
     *
     * A guess, and the screen says so: the nearest zone to a point in the
     * Atlantic is a real zone somebody does not live in. It is measured on the
     * flat, with the longitudes narrowed by the latitude they sit at, because a
     * great circle would cost more and decide the same winner at this spacing.
     * The wrap is what the correction cannot do without: Auckland and Suva sit
     * either side of the antimeridian, 12 degrees apart and 348 the other way.
     *
     * @return array{timezone: string, city: string, country: string, latitude: float, longitude: float}
     */
    public static function nearest(float $latitude, float $longitude, ?string $locale = null): array
    {
        $zones = self::all($locale);
        $nearest = $zones[0];
        $shortest = INF;

        foreach ($zones as $zone) {
            $dy = $zone['latitude'] - $latitude;
            $dx = $zone['longitude'] - $longitude;

            if ($dx > 180.0) {
                $dx -= 360.0;
            } elseif ($dx < -180.0) {
                $dx += 360.0;
            }

            $dx *= cos(deg2rad(($zone['latitude'] + $latitude) / 2));
            $distance = $dx * $dx + $dy * $dy;

            if ($distance < $shortest) {
                $shortest = $distance;
                $nearest = $zone;
            }
        }

        return $nearest;
    }

    /**
     * What a search matches on, one lowercased line per zone: the city, the
     * country and the identifier, so a reader may type any of the three.
     *
     * The browser filters this rather than asking the server on every keystroke,
     * which is how the field type picker searches its own catalogue.
     *
     * @return array<string, string>
     */
    public static function searchIndex(?string $locale = null): array
    {
        $index = [];

        foreach (self::all($locale) as $zone) {
            $index[$zone['timezone']] = mb_strtolower("{$zone['city']} {$zone['country']} {$zone['timezone']}");
        }

        return $index;
    }

    /**
     * Where a position falls in the map, as a percentage of its width and its
     * height, which is what places the marker and the hour band.
     *
     * @return array{x: float, y: float}
     */
    public static function position(float $latitude, float $longitude): array
    {
        return [
            'x' => round(($longitude + 180.0) / 360.0 * 100, 4),
            'y' => round((self::MAP_NORTH - $latitude) / (self::MAP_NORTH - self::MAP_SOUTH) * 100, 4),
        ];
    }

    /**
     * The city the identifier names, which is the part of it a reader
     * recognises. The region in front of it is dropped and the underscores
     * opened out, so `America/Argentina/Buenos_Aires` reads as `Buenos Aires`.
     */
    private static function cityOf(string $timezone): string
    {
        $segments = explode('/', $timezone);

        return str_replace('_', ' ', (string) end($segments));
    }

    /**
     * The country in the language being read, which ICU knows and we do not
     * write down. `??` is the code the handful of zones belonging to no country
     * carry, and `UTC` is the one that matters: it is the column's default.
     */
    private static function countryOf(string $code, string $locale): string
    {
        if ($code === '??') {
            return '';
        }

        return Locale::getDisplayRegion("-{$code}", $locale);
    }
}
