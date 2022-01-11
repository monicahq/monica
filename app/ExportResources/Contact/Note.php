<?php

namespace App\ExportResources\Contact;

use App\ExportResources\ExportResource;

class Note extends ExportResource
{
    protected $columns = [
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'body',
        'is_favorite',
        'favorited_at',
    ];
}
