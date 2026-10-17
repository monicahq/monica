<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Helpers\CountedPhraseWordings;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactDeadlinesBlock
{
    /** @var list<string> */
    public const array FILTERS = ['all', 'overdue', 'today', 'upcoming'];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly bool $isAskedFor = false,
    ) {}

    /** @return list<array{category: string, row: ReminderRow}> */
    public function rows(): array
    {
        return once(function (): array {
            $rows = [];

            foreach ($this->reminders()->groups() as $group) {
                foreach ($group['rows'] as $row) {
                    $rows[] = ['category' => $group['key'], 'row' => $row];
                }
            }

            return $rows;
        });
    }

    /** @return list<array{key: string, label: string, count: int}> */
    public function filters(): array
    {
        return array_map(fn (array $filter): array => [
            'key' => $filter['key'],
            'label' => $filter['label'],
            'count' => $filter['count'],
        ], $this->reminders()->filters(self::FILTERS));
    }

    public function emptyTitle(string $filter): string
    {
        return $this->reminders()->emptyTitle($filter);
    }

    public function emptyLine(string $filter): string
    {
        return $this->reminders()->emptyLine($filter);
    }

    public function holdsNothing(): bool
    {
        return $this->rows() === [];
    }

    public function blankTitle(): string
    {
        return __('Nothing to remember about :name yet', ['name' => $this->firstName()]);
    }

    public function blankLine(): string
    {
        return __('Ask to be told about a date on their page, such as a birthday.');
    }

    public function remindersUrl(): string
    {
        return route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function allLabel(): string
    {
        return __('All of :name’s reminders', ['name' => $this->firstName()]);
    }

    public function canAdd(): bool
    {
        return ! $this->contact->is_archived;
    }

    public function isAdding(): bool
    {
        return $this->isAskedFor || old('cadence') !== null;
    }

    public function isAskedFor(): bool
    {
        return $this->isAskedFor;
    }

    /**
     * Whether the page carries the form. It is fetched when the reader asks
     * for it, so the page that only lists the reminders is spared the list of
     * every time zone.
     */
    public function isFormRendered(): bool
    {
        return $this->canAdd() && $this->isAdding();
    }

    public function formUrl(): string
    {
        return route('contacts.reminderForm.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function form(): ContactReminderForm
    {
        return once(fn (): ContactReminderForm => new ContactReminderForm(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
        ));
    }

    public function aboutLine(): string
    {
        return __('About :name', ['name' => $this->contact->name]);
    }

    /** @return array<string, string> */
    public function repeats(): array
    {
        return [
            ReminderCadenceEnum::Once->value => __('Once'),
            ReminderCadenceEnum::Weekly->value => __('Weekly'),
            ReminderCadenceEnum::Monthly->value => __('Monthly'),
            ReminderCadenceEnum::Yearly->value => __('Yearly'),
        ];
    }

    /** @return array<string, array<string, string>> */
    public function intervalWordings(): array
    {
        return [
            ReminderCadenceEnum::Weekly->value => CountedPhraseWordings::of('Every :count week|Every :count weeks'),
            ReminderCadenceEnum::Monthly->value => CountedPhraseWordings::of('Every :count month|Every :count months'),
            ReminderCadenceEnum::Yearly->value => CountedPhraseWordings::of('Every :count year|Every :count years'),
        ];
    }

    private function reminders(): ContactRemindersViewModel
    {
        return once(fn (): ContactRemindersViewModel => new ContactRemindersViewModel(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
        ));
    }

    private function firstName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }
}
