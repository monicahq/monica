<?php

namespace App\ExportResources\Contact;

use App\Services\Account\Settings\ExportResource;
use App\ExportResources\Instance\Emotion\EmotionName;

class Call extends ExportResource
{
    protected $columns = [
        'created_at',
        'updated_at',
    ];

    protected $properties = [
        'called_at',
        'content',
        'contact_called',
    ];

    public function data(): ?array
    {
        return  [
            'properties' => [
                $this->mergeWhen($this->emotions->count() > 0, [
                    'emotions' => EmotionName::collection($this->emotions),
                ]),
            ],
        ];
    }
}
