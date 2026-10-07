<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Helpers\NextReminderDeadlines;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ContactRemindersViewModel
{
    public const string FilterAll = 'all';

    /** @var list<string> */
    public const array FILTERS = ['all', 'overdue', 'today', 'upcoming', 'history'];

    /** @var array<string, list<ReminderRow>>|null */
    private ?array $rows = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly string $filter = self::FilterAll,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return __('Reminders');
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function canAdd(): bool
    {
        return ! $this->contact->is_archived;
    }

    public function newUrl(): string
    {
        return route('contacts.reminders.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function filter(): string
    {
        return in_array($this->filter, self::FILTERS, true) ? $this->filter : self::FilterAll;
    }

    /** @return list<array{key: string, label: string, count: int, url: string, isCurrent: bool}> */
    public function filters(): array
    {
        $rows = $this->rows();
        $labels = [
            'all' => __('All'),
            'overdue' => __('Overdue'),
            'today' => __('Today'),
            'upcoming' => __('Upcoming'),
            'history' => __('History'),
        ];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'count' => $key === self::FilterAll
                ? count($rows['overdue']) + count($rows['today']) + count($rows['upcoming'])
                : count($rows[$key]),
            'url' => route('contacts.reminders.index', array_filter([
                'vault' => $this->vault,
                'contact' => $this->contact,
                'filter' => $key === self::FilterAll ? null : $key,
            ])),
            'isCurrent' => $this->filter() === $key,
        ], self::FILTERS);
    }

    /** @return list<array{key: string, title: string, note: string, rows: list<ReminderRow>}> */
    public function groups(): array
    {
        $rows = $this->rows();
        $groups = [
            'overdue' => ['title' => __('Overdue'), 'note' => __('The day has passed without an answer.')],
            'today' => ['title' => __('Today'), 'note' => ''],
            'upcoming' => ['title' => __('Upcoming'), 'note' => __('Soonest first.')],
            'history' => ['title' => __('History'), 'note' => __('What you answered. Answers are kept as they are.')],
        ];

        $keys = match ($this->filter()) {
            self::FilterAll => ['overdue', 'today', 'upcoming'],
            default => [$this->filter()],
        };

        $shown = [];

        foreach ($keys as $key) {
            if ($rows[$key] === []) {
                continue;
            }

            $shown[] = ['key' => $key, ...$groups[$key], 'rows' => $rows[$key]];
        }

        return $shown;
    }

    public function emptyTitle(?string $filter = null): string
    {
        return match ($filter ?? $this->filter()) {
            'overdue' => __('Nothing overdue'),
            'today' => __('Nothing today'),
            'upcoming' => __('Nothing coming up'),
            'history' => __('No answers yet'),
            default => __('No reminders about :name yet', ['name' => $this->contact->name]),
        };
    }

    public function emptyLine(?string $filter = null): string
    {
        return match ($filter ?? $this->filter()) {
            'overdue' => __('Reminders whose day has passed without an answer land here.'),
            'today' => __('Reminders due today land here, in your time zone.'),
            'upcoming' => __('Reminders you add, and dates on this page you ask to be told about, land here.'),
            'history' => __('Each time you answer I remembered or I forgot, it is kept here.'),
            default => __('Add one yourself, or ask to be told about a date on their page, such as a birthday.'),
        };
    }

    public function summary(): ReminderAnswerSummary
    {
        return new ReminderAnswerSummary(user: $this->user, contact: $this->contact);
    }

    /** @return array{overdue: list<ReminderRow>, today: list<ReminderRow>, upcoming: list<ReminderRow>, history: list<ReminderRow>} */
    private function rows(): array
    {
        if ($this->rows !== null) {
            /** @var array{overdue: list<ReminderRow>, today: list<ReminderRow>, upcoming: list<ReminderRow>, history: list<ReminderRow>} */
            return $this->rows;
        }

        $today = CarbonImmutable::now($this->user->timezone)->startOfDay();
        $rows = ['overdue' => [], 'today' => [], 'upcoming' => [], 'history' => []];

        $waiting = ReminderOccurrence::query()
            ->whereIn('reminder_id', $this->activeRules()->modelKeys())
            ->active()
            ->whereDate('local_date', '<=', $today->toDateString())
            ->orderBy('local_date')
            ->get()
            ->each($this->attachVault(...));

        foreach ($waiting as $occurrence) {
            $occurrence->setRelation('reminder', $this->rules()->find($occurrence->reminder_id));
            $rows[ReminderOccurrenceCategoryEnum::for($occurrence, $today)->value][] = $this->rowFor($occurrence);
        }

        $upcoming = NextReminderDeadlines::for($this->activeRules(), $today);

        foreach ($upcoming as $occurrence) {
            $rows['upcoming'][] = $this->rowFor($occurrence);
        }

        $rows['history'] = ReminderOccurrence::query()
            ->whereIn('reminder_id', $this->rules()->modelKeys())
            ->answered()
            ->orderByDesc('answered_at')
            ->get()
            ->each($this->attachVault(...))
            ->map(fn (ReminderOccurrence $occurrence): ReminderRow => $this->rowFor(
                $occurrence->setRelation('reminder', $this->rules()->find($occurrence->reminder_id)),
            ))
            ->all();

        return $this->rows = $rows;
    }

    /** @return EloquentCollection<int, Reminder> */
    private function rules(): EloquentCollection
    {
        return once(fn () => Reminder::withTrashed()
            ->where('account_id', $this->contact->account_id)
            ->where('remindable_type', $this->contact->getMorphClass())
            ->where('remindable_id', $this->contact->id)
            ->with(['field', 'fieldValue'])
            ->get()
            ->each(function (Reminder $rule): void {
                $rule->setRelation('remindable', $this->contact);
                $this->attachVault($rule);
            }));
    }

    /**
     * Every reminder of the contact is kept in the contact's vault, which is
     * already loaded, so it is handed over rather than fetched again. A record
     * kept elsewhere still loads its own.
     */
    private function attachVault(Reminder|ReminderOccurrence $record): void
    {
        if ($record->vault_id !== $this->vault->id) {
            return;
        }

        $record->setRelation('vault', $this->vault);
    }

    /** @return EloquentCollection<int, Reminder> */
    private function activeRules(): EloquentCollection
    {
        return $this->rules()->filter(fn (Reminder $rule): bool => $rule->deleted_at === null && $rule->is_active);
    }

    private function rowFor(ReminderOccurrence $occurrence): ReminderRow
    {
        return new ReminderRow(user: $this->user, occurrence: $occurrence);
    }
}
