<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeZone;
use Locale;

class TimezoneCatalogue
{
    public const float MAP_WIDTH = 1000.0;

    public const float MAP_HEIGHT = 394.44;

    public const float MAP_NORTH = 84.0;

    public const float MAP_SOUTH = -58.0;

    /** @var array<string, array<int, array{timezone: string, city: string, country: string, latitude: float, longitude: float}>> */
    private static array $zones = [];

    /** @return array<int, array{timezone: string, city: string, country: string, latitude: float, longitude: float}> */
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

    /** @return array{timezone: string, city: string, country: string, latitude: float, longitude: float}|null */
    public static function find(string $timezone, ?string $locale = null): ?array
    {
        foreach (self::all($locale) as $zone) {
            if ($zone['timezone'] === $timezone) {
                return $zone;
            }
        }

        return null;
    }

    /** @return array{timezone: string, city: string, country: string, latitude: float, longitude: float} */
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

    /** @return array<string, string> */
    public static function searchIndex(?string $locale = null): array
    {
        $index = [];

        foreach (self::all($locale) as $zone) {
            $index[$zone['timezone']] = mb_strtolower("{$zone['city']} {$zone['country']} {$zone['timezone']}");
        }

        return $index;
    }

    /** @return array{x: float, y: float} */
    public static function position(float $latitude, float $longitude): array
    {
        return [
            'x' => round(($longitude + 180.0) / 360.0 * 100, 4),
            'y' => round((self::MAP_NORTH - $latitude) / (self::MAP_NORTH - self::MAP_SOUTH) * 100, 4),
        ];
    }

    private static function cityOf(string $timezone): string
    {
        $segments = explode('/', $timezone);

        return str_replace('_', ' ', (string) end($segments));
    }

    private static function countryOf(string $code, string $locale): string
    {
        if ($code === '??') {
            return '';
        }

        return Locale::getDisplayRegion("-{$code}", $locale);
    }
}
