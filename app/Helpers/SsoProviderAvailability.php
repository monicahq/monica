<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\SsoProviderEnum;

class SsoProviderAvailability
{
    /**
     * Get the providers this instance offers, meaning the ones whose driver is
     * installed and whose credentials the operator has supplied. Order follows
     * the enum so the sign in screen is the same on every instance.
     *
     * @return list<SsoProviderEnum>
     */
    public static function onThisInstance(): array
    {
        return array_values(array_filter(
            SsoProviderEnum::cases(),
            fn (SsoProviderEnum $provider): bool => self::isAvailable($provider),
        ));
    }

    public static function isAvailable(SsoProviderEnum $provider): bool
    {
        return self::isInstalled($provider) && self::isConfigured($provider);
    }

    public static function anyOnThisInstance(): bool
    {
        return self::onThisInstance() !== [];
    }

    private static function isInstalled(SsoProviderEnum $provider): bool
    {
        return in_array($provider->value, config('monica.sso_installed', []), true);
    }

    private static function isConfigured(SsoProviderEnum $provider): bool
    {
        foreach ($provider->requiredSettings() as $setting) {
            if (blank(config("services.{$provider->value}.{$setting}"))) {
                return false;
            }
        }

        return true;
    }
}
