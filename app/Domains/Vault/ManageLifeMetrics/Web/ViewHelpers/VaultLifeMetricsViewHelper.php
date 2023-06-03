<?php

namespace App\Domains\Vault\ManageLifeMetrics\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\LifeMetric;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sorry if you need to read this code. I'm not proud of it.
 * It's messy, it's inelegant.
 * I don't like to deal with data like this.
 */
class VaultLifeMetricsViewHelper
{
    public static function data(Vault $vault, User $user, int $year): array
    {
        $lifeMetrics = $vault->lifeMetrics;
        $contact = $user->getContactInVault($vault);

        $lifeMetricsCollection = $lifeMetrics->map(fn (LifeMetric $lifeMetric) => self::dto($lifeMetric, $year, $contact));

        return [
            'data' => $lifeMetricsCollection,
            'url' => [
                'store' => route('vault.life_metrics.store', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function dto(LifeMetric $lifeMetric, int $year, Contact $contact): array
    {
        $events = $contact->lifeMetrics()
            ->where('life_metric_id', $lifeMetric->id)
            ->get();

        // get all the events for the given year
        $monthlyEvents = $events->filter(fn ($lifeMetricEvent) => Carbon::parse($lifeMetricEvent->pivot->created_at)->year === $year
        );
        $maxNumberOfEvents = 0;
        $eventsInMonthCollection = collect();
        for ($month = 1; $month < 13; $month++) {
            $eventsCounter = 0;

            foreach ($monthlyEvents as $monthlyEvent) {
                if (CarbonImmutable::parse($monthlyEvent->pivot->created_at)->month === $month) {
                    $eventsCounter++;
                }
            }

            if ($maxNumberOfEvents < $eventsCounter) {
                $maxNumberOfEvents = $eventsCounter;
            }

            $date = CarbonImmutable::now()->day(1)->month($month);
            $eventsInMonthCollection->push([
                'id' => $month,
                'friendly_name' => DateHelper::formatMonthNumber($date),
                'events' => $eventsCounter,
            ]);
        }

        return [
            'id' => $lifeMetric->id,
            'incremented' => false,
            'show_graph' => false,
            'label' => $lifeMetric->label,
            'stats' => self::stats($lifeMetric, $contact),
            'years' => self::years($lifeMetric, $contact),
            'months' => $eventsInMonthCollection,
            'max_number_of_events' => $maxNumberOfEvents,
            'url' => [
                'store' => route('vault.life_metrics.contact.store', [
                    'vault' => $contact->vault->id,
                    'metric' => $lifeMetric->id,
                ]),
                'update' => route('vault.life_metrics.update', [
                    'vault' => $contact->vault->id,
                    'metric' => $lifeMetric->id,
                ]),
                'destroy' => route('vault.life_metrics.destroy', [
                    'vault' => $contact->vault->id,
                    'metric' => $lifeMetric->id,
                ]),
            ],
        ];
    }

    public static function years(LifeMetric $lifeMetric, Contact $contact): Collection
    {
        $events = $contact->lifeMetrics()
            ->where('life_metric_id', $lifeMetric->id)
            ->get();

        $yearsCollection = $events->map(fn (LifeMetric $lifeMetricEvent) => [
            'year' => Carbon::parse($lifeMetricEvent->pivot->created_at)->year,
        ]);

        return $yearsCollection->unique('year')->sortByDesc('year')->values();
    }

    public static function stats(LifeMetric $lifeMetric, Contact $contact): array
    {
        // get all the events of the current week
        $weeklyEvents = $contact->lifeMetrics()
            ->where('life_metric_id', $lifeMetric->id)
            ->wherePivot('created_at', '<=', CarbonImmutable::now()->endOfWeek())
            ->wherePivot('created_at', '>=', CarbonImmutable::now()->startOfWeek())
            ->count();

        // get all the events of the current month
        $monthlyEvents = $contact->lifeMetrics()
            ->where('life_metric_id', $lifeMetric->id)
            ->wherePivot('created_at', '<=', CarbonImmutable::now()->endOfMonth())
            ->wherePivot('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();

        // get all the events of the current year
        $yearlyEvents = $contact->lifeMetrics()
            ->where('life_metric_id', $lifeMetric->id)
            ->wherePivot('created_at', '<=', CarbonImmutable::now()->endOfYear())
            ->wherePivot('created_at', '>=', CarbonImmutable::now()->startOfYear())
            ->count();

        return [
            'weekly_events' => $weeklyEvents,
            'monthly_events' => $monthlyEvents,
            'yearly_events' => $yearlyEvents,
        ];
    }
}
