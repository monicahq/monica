<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Profile;

use App\Models\User;

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
}
