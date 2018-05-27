<?php

namespace App\Http\Resources\Settings\CustomField;

use Illuminate\Http\Resources\Json\Resource;

class FieldChoice extends Resource
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
            'object' => 'fieldchoice',
            'value' => $this->value,
            'is_default' => (bool) $this->is_default,
            'field' => [
                'id' => $this->field->id,
            ],
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
