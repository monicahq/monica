<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\PasskeyAvailability;
use App\Models\User;
use Database\Seeders\ExampleAccountSeeder;

class LoginViewModel extends GuestViewModel
{
    /**
     * Whether the screen may offer to sign in with a passkey.
     *
     * The browser checks too, and it has the last word, because only it knows
     * what the device in front of the person can do. This check is about the
     * instance: served over a connection a passkey will not travel on, or with
     * the feature switched off, the control is not rendered at all and the
     * reason takes its place. A control that fails when used is worse than one
     * that is not there.
     */
    public function passkeysAreAvailable(): bool
    {
        return PasskeyAvailability::onThisInstance();
    }

    /**
     * Why passkeys cannot be used here, or null when they can.
     */
    public function passkeysUnavailableReason(): ?string
    {
        return PasskeyAvailability::reason();
    }

    /**
     * The address the shortcut on a developer's machine signs in, or null when
     * nobody has seeded the example account.
     *
     * It is read from the seeder rather than written here, because the two
     * drifting apart is exactly what left the screen offering to sign in as
     * somebody the seeder had stopped creating.
     *
     * The absence is answered rather than assumed. Login links are configured
     * not to invent a user, since one made out of thin air would belong to no
     * account, so a shortcut pointing at an address nobody seeded throws when
     * it is clicked. Not offering it is the better answer.
     */
    public function exampleAccountEmail(): ?string
    {
        $exists = User::query()->where('email', ExampleAccountSeeder::EMAIL)->exists();

        return $exists ? ExampleAccountSeeder::EMAIL : null;
    }

    /**
     * Who the shortcut signs in, for the label to name.
     */
    public function exampleAccountName(): string
    {
        return ExampleAccountSeeder::FIRST_NAME.' '.ExampleAccountSeeder::LAST_NAME;
    }
}
