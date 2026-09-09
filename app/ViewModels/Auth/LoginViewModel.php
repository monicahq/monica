<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
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

    public function magicLinkIsEnabled(): bool
    {
        return (bool) config('monica.magic_link_enabled');
    }
}
