<?php

namespace App\ExportResources\Account;

use App\ExportResources\ExportResource;

class ReminderRule extends ExportResource
{
    protected $columns = [
        'number_of_days_before',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'active',
    ];
}
