<?php

namespace App\ExportResources\Contact;

use App\Services\Account\Settings\ExportResource;

class Note extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'body',
        'is_favorite',
        'favorited_at',
    ];
}
