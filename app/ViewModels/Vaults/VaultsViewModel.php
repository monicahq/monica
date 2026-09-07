<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;

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
        return __('A vault keeps one part of your life separate from the others. People, notes and reminders never move between them.');
    }

    public function orderNote(): string
    {
        return __('The one you were last in is first.');
    }

    public function createUrl(): string
    {
        return route('vaults.new');
    }

    /**
     * @return array<int, array{name: string, initial: string, description: string|null, colour: string, tint: string, contactCount: string, isDefault: bool, isLastOpened: bool, url: string}>
     */
    public function vaults(): array
    {
        $vaults = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderByRaw('last_visited_at is null')
            ->orderByDesc('last_visited_at')
            ->orderBy('position')
            ->get();

        return $vaults
            ->map(fn (Vault $vault, int $index): array => [
                'name' => $vault->name,
                'initial' => mb_strtoupper(mb_substr($vault->name, 0, 1)),
                'description' => $vault->description,
                'colour' => $vault->colour,
                'tint' => "color-mix(in srgb, {$vault->colour} 12%, transparent)",
                'contactCount' => $this->contactCount($vault),
                'isDefault' => $vault->is_default,
                'isLastOpened' => $index === 0 && $vault->last_visited_at !== null,
                'url' => route('vaults.show', $vault),
            ])
            ->all();
    }

    private function contactCount(Vault $vault): string
    {
        if ($vault->contact_count === 0) {
            return __('Nobody in it yet');
        }

        return trans_choice(':count person|:count people', $vault->contact_count);
    }

    public function user(): User
    {
        return $this->user;
    }
}
