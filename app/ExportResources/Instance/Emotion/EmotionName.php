<?php

namespace App\ExportResources\Instance\Emotion;

use App\Services\Account\Settings\ExportResource;

class EmotionName extends ExportResource
{
    protected $columns = [
        'name',
    ];
}
