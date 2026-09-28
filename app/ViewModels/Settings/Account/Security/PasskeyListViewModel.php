<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;
use Laravel\Passkeys\Passkey;

class PasskeyListViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<int, array{id: int, name: string, addedAt: string, lastUsedAt: ?string}> */
    public function passkeys(): array
    {
        return $this->user->passkeys()
            ->orderByRaw('last_used_at IS NULL')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name !== '_' ? $passkey->name : ($passkey->authenticator ?? __('This device')),
                'addedAt' => $passkey->created_at->diffForHumans(),
                'lastUsedAt' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    public function hasPasskeys(): bool
    {
        return $this->user->hasPasskeys();
    }

    public function header(): string
    {
        $count = $this->user->passkeys()->count();

        if ($count === 0) {
            return __('None yet');
        }

        return trans_choice('Active · :count passkey|Active · :count passkeys', $count);
    }
}
