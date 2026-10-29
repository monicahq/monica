<?php

declare(strict_types=1);

namespace App\Helpers;

class DeviceDescription
{
    private const array BROWSERS = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'Opera' => 'Opera',
        'CriOS/' => 'Chrome',
        'Chrome/' => 'Chrome',
        'FxiOS/' => 'Firefox',
        'Firefox/' => 'Firefox',
        'Safari/' => 'Safari',
    ];

    private const array SYSTEMS = [
        'iPhone' => 'iPhone',
        'iPad' => 'iPad',
        'Android' => 'Android',
        'Windows' => 'Windows',
        'Macintosh' => 'macOS',
        'Mac OS X' => 'macOS',
        'Linux' => 'Linux',
    ];

    /**
     * A short name for the browser and the system a user agent announces, such as "Chrome on
     * macOS". The order of each list matters: Edge also announces Chrome, and Chrome also
     * announces Safari, so the more particular marker is read first.
     */
    public static function fromUserAgent(?string $userAgent): string
    {
        $browser = self::firstMatch((string) $userAgent, self::BROWSERS);
        $system = self::firstMatch((string) $userAgent, self::SYSTEMS);

        if ($browser !== null && $system !== null) {
            return __(':browser on :system', ['browser' => $browser, 'system' => $system]);
        }

        return $browser ?? $system ?? __('Unknown device');
    }

    /**
     * @param  array<string, string>  $markers
     */
    private static function firstMatch(string $userAgent, array $markers): ?string
    {
        foreach ($markers as $marker => $name) {
            if (str_contains($userAgent, $marker)) {
                return $name;
            }
        }

        return null;
    }
}
