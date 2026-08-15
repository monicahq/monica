<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;

/**
 * The compartments the account is divided into, and the way into each. The
 * one somebody was last in comes first, because the reason to be on this
 * screen is almost always to go back where you were.
 *
 * The card says the three things that tell one vault from another: what it is
 * called, what it is for, and how many people are in it. Everything else the
 * design asks for needs a model that does not exist, and an invented sentence
 * about a real person is worse than a shorter card.
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
        return __('A vault keeps one part of your life separate from the others. People, notes and reminders never cross between them.');
    }

    /**
     * The line beside the heading of the list. It says what the order is, which
     * is the one thing about this list somebody cannot work out by reading it.
     */
    public function orderNote(): string
    {
        return __('The one you were last in is first.');
    }

    public function createUrl(): string
    {
        return route('vaults.new');
    }

    /**
     * Every vault of the account, the most recently opened first. A vault
     * nobody has opened yet falls in behind the ones that were, said here
     * rather than left to the database: Postgres ranks null above everything
     * on a descending order and MySQL and SQLite rank it below.
     *
     * The mark on a card takes the colour somebody chose for the vault rather
     * than one derived from its name.
     *
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

        if ($vault->contact_count === 1) {
            return __('1 person');
        }

        return __(':count people', ['count' => $vault->contact_count]);
    }

    public function user(): User
    {
        return $this->user;
    }
}
