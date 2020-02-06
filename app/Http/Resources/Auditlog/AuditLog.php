<?php

namespace App\Http\Resources\AuditLog;

use App\Helpers\DateHelper;
use function Safe\json_decode;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Emotion\Emotion as EmotionResource;
use App\Http\Resources\Activity\ActivityType as ActivityTypeResource;

class AuditLog extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'auditlog',
            'author' => ($this->author) ? [
                'id' => $this->id,
                'name' => $this->name,
            ] : [
                'name' => $this->author_name,
            ],
            'action' => $this->action,
            'objects' => json_decode($this->objects),
            'audited_at' => DateHelper::getTimestamp($this->audited_at),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
