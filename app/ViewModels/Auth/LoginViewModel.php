<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Enums\SsoProviderEnum;
use App\Helpers\PasskeyAvailability;
use App\Helpers\SsoProviderAvailability;
use App\Models\User;
use Database\Seeders\ExampleAccountSeeder;

class LoginViewModel extends GuestViewModel
{
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    public function exampleAccountEmail(): ?string
    {
        $exists = User::query()->where('email', ExampleAccountSeeder::EMAIL)->exists();

        return $exists ? ExampleAccountSeeder::EMAIL : null;
    }

    public function exampleAccountName(): string
    {
        return ExampleAccountSeeder::FIRST_NAME.' '.ExampleAccountSeeder::LAST_NAME;
    }

    /**
     * Get the providers this instance offers, each with what the control needs
     * to name it and where choosing it leads.
     *
     * @return list<array{value: string, name: string, url: string}>
     */
    public function providersToOffer(): array
    {
        return array_map(fn (SsoProviderEnum $provider): array => [
            'value' => $provider->value,
            'name' => $provider->brandName(),
            'url' => route('auth.sso.redirect', ['provider' => $provider->value]),
        ], SsoProviderAvailability::onThisInstance());
    }

    public function magicLinkIsEnabled(): bool
    {
        return (bool) config('monica.magic_link_enabled');
    }
}
