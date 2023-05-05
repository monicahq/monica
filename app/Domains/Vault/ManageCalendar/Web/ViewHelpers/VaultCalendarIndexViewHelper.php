<?php

namespace App\Domains\Vault\ManageCalendar\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Models\ContactImportantDate;
use App\Models\MoodTrackingEvent;
use App\Models\Post;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class VaultCalendarIndexViewHelper
{
    public static function data(Vault $vault, User $user, int $year, int $month): array
    {
        $date = Carbon::createFromDate($year, $month, 1);
        $previousMonth = $date->copy()->subMonth();
        $nextMonth = $date->copy()->addMonth();

        $collection = self::buildMonth($vault, $user, $year, $month);

        return [
            'weeks' => $collection,
            'current_month' => DateHelper::formatLongMonthAndYear($date),
            'previous_month' => DateHelper::formatLongMonthAndYear($previousMonth),
            'next_month' => DateHelper::formatLongMonthAndYear($nextMonth),
            'url' => [
                'previous' => route('vault.calendar.month', [
                    'vault' => $vault->id,
                    'year' => $previousMonth->year,
                    'month' => $previousMonth->month,
                ]),
                'next' => route('vault.calendar.month', [
                    'vault' => $vault->id,
                    'year' => $nextMonth->year,
                    'month' => $nextMonth->month,
                ]),
            ],
        ];
    }

    /**
     * Completely copied from https://tighten.com/insights/building-a-calendar-with-carbon/.
     */
    public static function buildMonth(Vault $vault, User $user, int $year, int $month): Collection
    {
        $firstDayOfMonth = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $lastDayOfMonth = CarbonImmutable::create($year, $month, 1)->endOfMonth();
        $startOfWeek = $firstDayOfMonth->startOfWeek();
        $endOfWeek = $lastDayOfMonth->endOfWeek();
        $contactsId = $vault->contacts()->pluck('id');

        // @phpstan-ignore-next-line
        return collect($startOfWeek->toPeriod($endOfWeek)->toArray())
            ->map(fn (CarbonImmutable $day) => [
                'id' => $day->day,
                'date' => $day->format('d'),
                'is_today' => $day->isToday(),
                'is_in_month' => $day->month === $firstDayOfMonth->month,
                'important_dates' => self::getImportantDates($day->month, $day->day, $contactsId),
                'mood_events' => self::getMood($vault, $user, $day),
                'posts' => self::getJournalEntries($vault, $day),
                'url' => [
                    'show' => route('vault.calendar.day', [
                        'vault' => $vault->id,
                        'year' => $day->year,
                        'month' => $day->month,
                        'day' => $day->day,
                    ]),
                ],
            ])
            ->chunk(7);
    }

    public static function getImportantDates(int $month, int $day, Collection $contactsId): Collection
    {
        return ContactImportantDate::where('day', $day)
            ->where('month', $month)
            ->whereIn('contact_id', $contactsId)
            ->with('contact')
            ->get()
            ->unique('contact_id')
            ->map(fn (ContactImportantDate $importantDate) => [
                'id' => $importantDate->id,
                'label' => $importantDate->label,
                'type' => [
                    'id' => $importantDate->contactImportantDateType?->id,
                    'label' => $importantDate->contactImportantDateType?->label,
                ],
                'contact' => ContactCardHelper::data($importantDate->contact),
            ]);
    }

    public static function getMood(Vault $vault, User $user, CarbonImmutable $date): Collection
    {
        $contact = $user->getContactInVault($vault);

        return $contact->moodTrackingEvents()
            ->with('moodTrackingParameter')
            ->whereDate('rated_at', $date)
            ->get()
            ->map(fn (MoodTrackingEvent $moodTrackingEvent) => [
                'id' => $moodTrackingEvent->id,
                'note' => $moodTrackingEvent->note,
                'number_of_hours_slept' => $moodTrackingEvent->number_of_hours_slept,
                'mood_tracking_parameter' => [
                    'id' => $moodTrackingEvent->moodTrackingParameter->id,
                    'label' => $moodTrackingEvent->moodTrackingParameter->label,
                    'hex_color' => $moodTrackingEvent->moodTrackingParameter->hex_color,
                ],
            ]);
    }

    public static function getJournalEntries(Vault $vault, CarbonImmutable $day): Collection
    {
        $journalIds = $vault->journals->pluck('id');

        return Post::whereIn('journal_id', $journalIds)
            ->whereDate('written_at', $day)
            ->with('journal')
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'url' => [
                    'show' => route('post.show', [
                        'vault' => $post->journal->vault_id,
                        'journal' => $post->journal_id,
                        'post' => $post->id,
                    ]),
                ],
            ]);
    }

    public static function getDayInformation(Vault $vault, User $user, int $year, int $month, int $day): array
    {
        $date = Carbon::createFromDate($year, $month, $day);
        $immutableDate = CarbonImmutable::createFromDate($year, $month, $day);
        $contactsId = $vault->contacts()->pluck('id');

        return [
            'day' => DateHelper::formatFullDate($date),
            'important_dates' => self::getImportantDates($date->month, $date->day, $contactsId),
            'mood_events' => self::getMood($vault, $user, $immutableDate),
            'posts' => self::getJournalEntries($vault, $immutableDate),
        ];
    }
}
