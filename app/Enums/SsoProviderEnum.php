<?php

declare(strict_types=1);

namespace App\Enums;

enum SsoProviderEnum: string
{
    case GitHub = 'github';
    case Google = 'google';
    case Microsoft = 'microsoft';
    case Facebook = 'facebook';

    /**
     * Get the provider's name as its own company writes it. It is a proper noun,
     * identical in every language, so it is not translated and this enum carries
     * no `label()`. The copy around it, `Continue with :provider`, is.
     */
    public function brandName(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Google => 'Google',
            self::Microsoft => 'Microsoft',
            self::Facebook => 'Facebook',
        };
    }

    public function driverClass(): ?string
    {
        return match ($this) {
            self::Microsoft => 'SocialiteProviders\Microsoft\Provider',
            default => null,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
