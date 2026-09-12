<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Profile;

use App\Models\User;
use App\ViewModels\Auth\ConfirmPasswordViewModel;

class ProfileViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array{first_name: string|null, last_name: string|null, email: string|null} */
    public function details(): array
    {
        return [
            'first_name' => old('first_name', $this->user->first_name),
            'last_name' => old('last_name', $this->user->last_name),
            'email' => old('email', $this->user->email),
        ];
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * Whether a subscription is running, which is what the deletion warning has to say will stop.
     */
    public function isSubscribed(): bool
    {
        return $this->user->account->subscription()?->valid() === true;
    }

    public function name(): string
    {
        return $this->user->name;
    }

    public function hasPhoto(): bool
    {
        return $this->user->hasPhoto();
    }

    public function email(): string
    {
        return $this->user->email;
    }

    public function lastSavedAt(): ?string
    {
        return $this->user->last_saved_at?->diffForHumans();
    }

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    public function passwordIsConfirmed(): bool
    {
        return $this->secondsBeforeTheConfirmationExpires() > 0;
    }

    /**
     * What is left of the window when the screen is drawn. A page left open outlives its own
     * confirmation, so the screen has to hand the browser a deadline rather than a yes or no.
     */
    public function secondsBeforeTheConfirmationExpires(): int
    {
        $confirmedAt = session('auth.password_confirmed_at');

        if ($confirmedAt === null) {
            return 0;
        }

        return max(0, (int) config('auth.password_timeout') - (time() - (int) $confirmedAt));
    }
}
