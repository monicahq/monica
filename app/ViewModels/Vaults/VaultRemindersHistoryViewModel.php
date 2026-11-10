<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VaultRemindersHistoryViewModel
{
    public const int PER_PAGE = 25;

    /** @var Paginator<int, ReminderOccurrence>|null */
    private ?Paginator $occurrences = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function title(): string
    {
        return __('Reminders');
    }

    /** @return list<ReminderRow> */
    public function rows(): array
    {
        return array_map(
            fn (ReminderOccurrence $occurrence): ReminderRow => new ReminderRow(
                user: $this->user,
                occurrence: $occurrence,
            ),
            $this->occurrences()->items(),
        );
    }

    public function hasMorePages(): bool
    {
        return $this->occurrences()->hasMorePages();
    }

    public function nextPageUrl(): ?string
    {
        return $this->occurrences()->nextPageUrl();
    }

    public function emptyTitle(): string
    {
        return __('No answers yet');
    }

    public function emptyLine(): string
    {
        return __('Each time you answer I remembered or I forgot, it is kept here. Answers are kept as they are.');
    }

    public function activeUrl(): string
    {
        return route('reminders.index', $this->vault);
    }

    public function sentUrl(): string
    {
        return route('reminders.sent', $this->vault);
    }

    public function activeCount(): int
    {
        return new VaultRemindersViewModel(user: $this->user, vault: $this->vault)->activeCount();
    }

    public function sentCount(): int
    {
        return ReminderOccurrence::query()->sentInVault($this->vault)->count();
    }

    public function historyUrl(): string
    {
        return route('reminders.history', $this->vault);
    }

    public function historyCount(): int
    {
        return ReminderOccurrence::query()->answeredInVault($this->vault)->count();
    }

    /** @return Paginator<int, ReminderOccurrence> */
    private function occurrences(): Paginator
    {
        if ($this->occurrences !== null) {
            return $this->occurrences;
        }

        $page = ReminderOccurrence::query()
            ->answeredInVault($this->vault)
            ->with(['vault'])
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();

        $rules = Reminder::withTrashed()
            ->whereIn('id', collect($page->items())->pluck('reminder_id')->unique())
            ->with(['remindable' => fn (MorphTo $record): MorphTo => $record->morphWith([Contact::class => ['avatar'], Entity::class => ['avatar']]), 'field'])
            ->get()
            ->keyBy('id');

        foreach ($page->items() as $occurrence) {
            $occurrence->setRelation('reminder', $rules->get($occurrence->reminder_id));
        }

        return $this->occurrences = $page;
    }
}
