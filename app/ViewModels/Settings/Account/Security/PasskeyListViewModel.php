<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\Passkey;
use App\Models\User;

class PasskeyListViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<int, array{id: int, name: string, addedAt: string, lastUsedAt: ?string}> */
    public function passkeys(): array
    {
        return Passkey::query()
            ->where('user_id', $this->user->id)
            ->orderByRaw('last_used_at IS NULL')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->displayName(),
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
