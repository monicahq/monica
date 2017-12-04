<?php

namespace App\Http\Resources\Settings\ContactFieldType;

use Illuminate\Http\Resources\Json\Resource;

class ContactFieldType extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'contactfieldtype',
            'name' => $this->name,
            'fontawesome_icon' => $this->fontawesome_icon,
            'protocol' => $this->protocol,
            'delible' => (bool) $this->delible,
            'type' => $this->type,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
