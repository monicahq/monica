<?php

namespace App\Http\Resources\Settings\CustomField;

use Illuminate\Http\Resources\Json\Resource;

class CustomField extends Resource
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
            'object' => 'customfield',
            'name' => $this->name,
            'fields_order' => $this->fields_order,
            'is_list' => (bool) $this->is_list,
            'is_important' => (bool) $this->is_important,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
