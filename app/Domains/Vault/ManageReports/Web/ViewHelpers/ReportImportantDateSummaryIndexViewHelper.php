<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Helpers\ContactCardHelper;
use App\Helpers\DateHelper;
use App\Helpers\ImportantDateHelper;
use App\Models\ContactImportantDate;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;

class ReportImportantDateSummaryIndexViewHelper
{
    /**
     * Get all the important dates for a given contact.
     * This screen will list all the important dates for the next 12 months.
     */
    public static function data(Vault $vault, User $user): array
    {
        $contactsInVault = $vault->contacts->pluck('id')->toArray();
        $importantDates = ContactImportantDate::whereIn('contact_id', $contactsInVault)
            ->with('contact')
            ->orderBy('month', 'asc')
            ->orderBy('day', 'asc')
            ->get();

        // create a loop looping over the next 12 months
        $currentDate = Carbon::now();
        $monthsCollection = collect();
        for ($month = 0; $month < 12; $month++) {
            $date = $currentDate->copy();
            $date->addMonths($month);

            $importantDatesCollection = collect();
            foreach ($importantDates as $importantDate) {
                if ($importantDate->month === $date->month) {
                    $importantDatesCollection->push([
                        'id' => $importantDate->id,
                        'label' => $importantDate->label,
                        'happened_at' => ImportantDateHelper::formatDate($importantDate, $user),
                        'happened_at_age' => ImportantDateHelper::getAge($importantDate),
                        'contact' => ContactCardHelper::data($importantDate->contact),
                    ]);
                }
            }

            $monthsCollection->push([
                'id' => $month,
                'month' => DateHelper::formatMonthAndYear($date),
                'important_dates' => $importantDatesCollection,
            ]);
        }

        return [
            'months' => $monthsCollection,
            'url' => [
                'reports' => route('vault.reports.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}
