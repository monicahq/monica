<?php

namespace App\ExportResources\Account;

use App\Services\Account\Settings\ExportResource;

class ContactActivity extends ExportResource
{
    protected $columns = [
        'uuid',
    ];
}
