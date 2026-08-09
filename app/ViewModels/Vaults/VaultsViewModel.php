<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;

/**
 * The list of compartments the account is divided into.
 *
 * This is a placeholder: the screen shows what a vault is called, what it is
 * for and how much it holds, in the order the user put them in, and nothing
 * else. What the list eventually becomes is its own design.
 */
class VaultsViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function title(): string
    {
        return __('Your vaults');
    }

    public function description(): string
    {
        return __('Each one is a sealed compartment. Nothing in a vault reaches another.');
    }

    public function createUrl(): string
    {
        return route('vaults.new');
    }

    /**
     * @return array<int, array{name: string, description: string|null, colour: string, isDefault: bool, contactCount: string}>
     */
    public function vaults(): array
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (Vault $vault): array => [
                'name' => $vault->name,
                'description' => $vault->description,
                'colour' => $vault->colour,
                'isDefault' => $vault->is_default,
                'contactCount' => $this->contactCount($vault),
            ])
            ->all();
    }

    private function contactCount(Vault $vault): string
    {
        if ($vault->contact_count === 1) {
            return __('1 contact');
        }

        return __(':count contacts', ['count' => $vault->contact_count]);
    }

    public function user(): User
    {
        return $this->user;
    }
}
