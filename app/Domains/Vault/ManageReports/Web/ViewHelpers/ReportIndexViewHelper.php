<?php

namespace App\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Models\Vault;

class ReportIndexViewHelper
{
    public static function data(Vault $vault): array
    {
        return [
            'url' => [
                'addresses' => route('vault.reports.addresses.index', [
                    'vault' => $vault->id,
                ]),
                'mood_tracking_events' => route('vault.reports.mood_tracking_events.index', [
                    'vault' => $vault->id,
                ]),
                'important_date_summary' => route('vault.reports.important_dates.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}
