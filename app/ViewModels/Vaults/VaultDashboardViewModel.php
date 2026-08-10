<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;

/**
 * What one vault shows when you open it: what is coming up, what you said you
 * would do, what you last wrote, and who you last looked at.
 *
 * Only the vault itself is real. Reminders, tasks, journal entries and the
 * people you opened recently are each their own model and none of them exists,
 * so this screen stands them up as invented rows to show the shape of the
 * thing. Every one of them is marked below, and every one of them goes when the
 * model it stands for arrives.
 *
 * The invented rows are deliberately not translated. They are stand-ins on
 * their way out, and putting a fictional task into five locale files would be
 * work thrown away the day the task model lands.
 */
class VaultDashboardViewModel
{
    /**
     * How many open tasks the invented rows below add up to. It is repeated in
     * the sidebar badge and in the count at the top of the screen, so it is
     * named once.
     */
    public const int PRETEND_OPEN_TASKS = 4;

    public const int PRETEND_JOURNAL_ENTRIES = 31;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return $this->vault->name;
    }

    public function description(): ?string
    {
        return $this->vault->description;
    }

    /**
     * The vaults the switcher offers, the one being looked at marked rather
     * than offered as a link to where you already are.
     *
     * @return array<int, array{name: string, colour: string, url: string, current: bool}>
     */
    public function vaults(): array
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (Vault $vault): array => [
                'name' => $vault->name,
                'colour' => $vault->colour,
                'url' => route('vaults.show', $vault),
                'current' => $vault->is($this->vault),
            ])
            ->all();
    }

    /**
     * The three numbers at the top. Only the first is counted from anything:
     * the other two are the invented rows below, counted so the screen at
     * least agrees with itself.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function stats(): array
    {
        return [
            ['value' => (string) $this->vault->contact_count, 'label' => __('Contacts')],
            ['value' => (string) self::PRETEND_OPEN_TASKS, 'label' => __('Open tasks')],
            ['value' => (string) self::PRETEND_JOURNAL_ENTRIES, 'label' => __('Journal entries')],
        ];
    }

    /**
     * Invented. Dates in the next thirty days come from birthdays and from
     * reminder rules, and neither model exists.
     *
     * @return array<int, array{person: string, what: string, when: string, relative: string}>
     */
    public function reminders(): array
    {
        return [
            ['person' => 'Claire Bertin', 'what' => 'Birthday, turning 62', 'when' => '22 Aug', 'relative' => 'in 13 days'],
            ['person' => 'Samir Haddad', 'what' => 'Call him about the house', 'when' => '25 Aug', 'relative' => 'in 16 days'],
            ['person' => 'Nadia Rahman', 'what' => 'Wedding anniversary', 'when' => '2 Sep', 'relative' => 'in 24 days'],
            ['person' => 'Tom Whitfield', 'what' => 'Birthday, turning 35', 'when' => '6 Sep', 'relative' => 'in 28 days'],
        ];
    }

    /**
     * Invented, and drawn as done or not rather than ticked: there is nothing
     * to write a tick to.
     *
     * @return array<int, array{title: string, meta: string, done: bool}>
     */
    public function tasks(): array
    {
        return [
            ['title' => 'Send Nadia the removal company number', 'meta' => 'For Nadia Rahman, due Friday', 'done' => false],
            ['title' => 'Ask Tom how the second interview went', 'meta' => 'For Tom Whitfield, due 14 August', 'done' => false],
            ['title' => 'Book a table for the birthday dinner', 'meta' => 'For Claire Bertin, due 22 August', 'done' => false],
            ['title' => 'Return the drill to Samir', 'meta' => 'For Samir Haddad, no date', 'done' => false],
        ];
    }

    /**
     * Invented. The journal is its own model and its own screen.
     *
     * @return array<int, array{title: string, date: string, excerpt: string}>
     */
    public function entries(): array
    {
        return [
            ['title' => 'Sunday at the lake', 'date' => '4 Aug', 'excerpt' => 'Everyone came except Julien. Claire brought the tart she has been threatening to make since May, and it was worth the wait.'],
            ['title' => 'Coffee with Samir', 'date' => '28 Jul', 'excerpt' => 'He is thinking about moving back to Lyon. Nothing decided, but he sounded lighter about it than last time.'],
            ['title' => 'Nadia called', 'date' => '19 Jul', 'excerpt' => 'Short call. The move is happening in September and she wants help with the van.'],
        ];
    }

    /**
     * Invented. A contact is a real model, but nothing records when one was
     * last opened and there is no page to open, so the cards are made up and
     * lead nowhere.
     *
     * @return array<int, array{name: string, meta: string}>
     */
    public function recentlyOpened(): array
    {
        return [
            ['name' => 'Claire Bertin', 'meta' => 'Mother'],
            ['name' => 'Samir Haddad', 'meta' => 'Friend since 2009'],
            ['name' => 'Nadia Rahman', 'meta' => 'Cousin'],
            ['name' => 'Tom Whitfield', 'meta' => 'Brother in law'],
            ['name' => 'Elise Moreau', 'meta' => 'Aunt'],
            ['name' => 'Julien Bertin', 'meta' => 'Brother'],
        ];
    }

    public function vaultsUrl(): string
    {
        return route('vaults.index');
    }
}
