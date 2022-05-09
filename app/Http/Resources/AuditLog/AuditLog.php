<?php

namespace App\Http\Resources\AuditLog;

use App\Helpers\DateHelper;
use function Safe\json_decode;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Instance\AuditLog>
 */
class AuditLog extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'auditlog',
            'author' => ($this->author) ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
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
