<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReportMoodTrackingEventIndexViewHelper
{
    public static function data(Vault $vault, User $user, int $year): array
    {
        return [
            'months' => self::year($vault, $user, $year),
            'url' => [
                'reports' => route('vault.reports.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    /**
     * Get all the mood tracking events for the given year.
     */
    private static function year(Vault $vault, User $user, int $year): Collection
    {
        $contact = $user->getContactInVault($vault);

        $moodTrackingEvents = $contact->moodTrackingEvents()
            ->with('moodTrackingParameter')
            ->whereYear('rated_at', (string) $year)
            ->orderBy('rated_at', 'asc')
            ->get();

        // create the yearly calendar
        $monthsCollection = collect();
        for ($month = 1; $month < 13; $month++) {
            $currentMonth = CarbonImmutable::create($year, $month, 1);

            $daysCollection = collect();
            for ($day = 1; $day < $currentMonth->daysInMonth; $day++) {
                $date = CarbonImmutable::create($year, $month, $day);

                $moodTrackingForTheDay = null;
                foreach ($moodTrackingEvents as $moodTrackingEvent) {
                    if ($moodTrackingEvent->rated_at->month === $date->month &&
                        $moodTrackingEvent->rated_at->day === $date->day) {
                        $moodTrackingForTheDay = $moodTrackingEvent;
                    }
                }

                $daysCollection->push([
                    'id' => $day,
                    'event' => $moodTrackingForTheDay ? [
                        'id' => $moodTrackingForTheDay->id,
                        'hex_color' => $moodTrackingForTheDay->moodTrackingParameter->hex_color,
                    ] : null,
                ]);
            }

            $monthsCollection->push([
                'id' => $month,
                'month_word' => DateHelper::formatMonthNumber($currentMonth),
                'days' => $daysCollection,
            ]);
        }

        return $monthsCollection;
    }
}
