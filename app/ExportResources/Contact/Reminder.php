<?php

namespace App\ExportResources\Contact;

use App\Services\Account\Settings\ExportResource;

class Reminder extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'initial_date',
        'title',
        'description',
        'frequency_type',
        'frequency_number',
        'delible',
        'inactive',
    ];
}
