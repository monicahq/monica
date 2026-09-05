<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Security;

use App\Models\User;
use Laravel\Passkeys\Passkey;

/**
 * The passkeys on an account, as the security screen shows them.
 *
 * Most recently used first, because that is the order somebody looking for the
 * one to remove thinks in. A passkey never used says so rather than showing a
 * blank, which is the difference between "I have not used this yet" and "we lost
 * track of it".
 */
class PasskeyListViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array<int, array{id: int, name: string, addedAt: string, lastUsedAt: ?string}>
     */
    public function passkeys(): array
    {
        return $this->user->passkeys()
            ->orderByRaw('last_used_at IS NULL')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'addedAt' => $passkey->created_at->diffForHumans(),
                'lastUsedAt' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }

    public function hasPasskeys(): bool
    {
        return $this->user->hasPasskeys();
    }

    /**
     * The line at the head of the list, which says how many are in force rather
     * than making somebody count the rows.
     */
    public function header(): string
    {
        $count = $this->user->passkeys()->count();

        if ($count === 0) {
            return __('None yet');
        }

        return trans_choice('Active · :count passkey|Active · :count passkeys', $count);
    }
}
