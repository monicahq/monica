<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactReminderDates
{
    public const string History = 'history';

    /** @return list<string> */
    public static function categories(): array
    {
        return [...ReminderOccurrenceCategoryEnum::values(), self::History];
    }

    /** @return LengthAwarePaginator<int, ReminderOccurrence> */
    public static function page(User $user, Contact $contact, ?string $category, int $perPage, int $page): LengthAwarePaginator
    {
        $today = CarbonImmutable::now($user->timezone)->startOfDay();

        $rules = Reminder::withTrashed()
            ->where('account_id', $contact->account_id)
            ->where('remindable_type', $contact->getMorphClass())
            ->where('remindable_id', $contact->id)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->get();

        $activeRules = $rules->filter(fn (Reminder $rule): bool => $rule->deleted_at === null && $rule->is_active);

        if ($category === ReminderOccurrenceCategoryEnum::Upcoming->value) {
            $upcoming = NextReminderDeadlines::for($activeRules, $today);

            return new LengthAwarePaginator($upcoming->forPage($page, $perPage)->values(), $upcoming->count(), $perPage, $page);
        }

        $query = ReminderOccurrence::query()->whereIn('reminder_id', $rules->modelKeys())->with('vault');

        $query = match ($category) {
            ReminderOccurrenceCategoryEnum::Overdue->value => $query->whereIn('reminder_id', $activeRules->modelKeys())->active()->where('local_date', '<', $today->toDateString())->orderBy('local_date'),
            ReminderOccurrenceCategoryEnum::Today->value => $query->whereIn('reminder_id', $activeRules->modelKeys())->active()->where('local_date', '>=', $today->toDateString())->where('local_date', '<', $today->addDay()->toDateString()),
            self::History => $query->answered()->orderByDesc('answered_at'),
            default => $query->orderByDesc('local_date'),
        };

        $paginated = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        foreach ($paginated->items() as $occurrence) {
            $occurrence->setRelation('reminder', $rules->find($occurrence->reminder_id));
        }

        return $paginated;
    }
}
